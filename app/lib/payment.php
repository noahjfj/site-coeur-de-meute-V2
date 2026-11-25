<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/*
 * Acompte et paiements des séjours.
 * - Les réglages du virement (pourcentage, numéro de compte…) sont dans app/storage/paiement.json,
 *   modifiables par la gérante dans la page Tarifs. Ils ne sont JAMAIS envoyés au simulateur public.
 * - Chaque séjour garde son prix estimé, son acompte, une communication structurée belge
 *   (+++123/4567/89012+++) commune à toute la demande, et les dates de paiement.
 */

const DEFAULT_PAYMENT = ['pourcentage' => 10, 'iban' => '', 'titulaire' => 'Coeur de Meute', 'bic' => ''];

function payment_file(): string
{
    return rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/paiement.json';
}

function load_payment(): array
{
    $p = DEFAULT_PAYMENT;
    $raw = is_file(payment_file()) ? json_decode((string) file_get_contents(payment_file()), true) : null;
    if (is_array($raw)) {
        $p = array_merge($p, array_intersect_key($raw, $p));
    }
    $p['pourcentage'] = max(0, min(100, (float) $p['pourcentage']));
    return $p;
}

function save_payment(array $p): bool
{
    $file = payment_file();
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0750, true);
    }
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) {
        return false;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** IBAN nettoyé (sans espaces, en majuscules) s'il est valide, sinon null. */
function clean_iban(string $iban): ?string
{
    $iban = strtoupper(preg_replace('/[\s.\-]+/', '', $iban) ?? '');
    if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
        return null;
    }
    $moved = substr($iban, 4) . substr($iban, 0, 4);
    $digits = preg_replace_callback('/[A-Z]/', static fn($m) => (string) (ord($m[0]) - 55), $moved);
    $rest = 0;
    foreach (str_split($digits, 7) as $chunk) {
        $rest = (int) ($rest . $chunk) % 97;
    }
    return $rest === 1 ? $iban : null;
}

function format_iban(string $iban): string
{
    return trim(chunk_split($iban, 4, ' '));
}

/** Communication structurée belge, calculée à partir d'un numéro de demande. */
function structured_ref(int $id): string
{
    $base = sprintf('%010d', $id % 10000000000);
    $check = (int) $base % 97;
    $n = $base . sprintf('%02d', $check === 0 ? 97 : $check);
    return '+++' . substr($n, 0, 3) . '/' . substr($n, 3, 4) . '/' . substr($n, 7, 5) . '+++';
}

function deposit_for(float $total): float
{
    return round($total * load_payment()['pourcentage'] / 100, 2);
}

/** Texte « comment payer l'acompte » pour un groupe de séjours (même communication). */
function deposit_text(array $rows): string
{
    $p = load_payment();
    $total = array_sum(array_map(static fn($r) => (float) $r['price_total'], $rows));
    $deposit = array_sum(array_map(static fn($r) => (float) $r['deposit_amount'], $rows));
    if ($deposit <= 0) {
        return '';
    }
    $pct = rtrim(rtrim(number_format($p['pourcentage'], 2, ',', ''), '0'), ',');
    $text = 'Pour bloquer vos dates, merci de verser un acompte de ' . $pct . ' % du prix du séjour.' . "\n\n"
        . 'Montant à verser : ' . euros($deposit) . "\n";
    if ($p['iban'] !== '') {
        $text .= 'Sur le compte : ' . format_iban($p['iban']) . ($p['titulaire'] !== '' ? ' (' . $p['titulaire'] . ')' : '') . "\n"
            . ($p['bic'] !== '' ? 'BIC : ' . $p['bic'] . "\n" : '');
    } else {
        $text .= "Le numéro de compte vous sera communiqué par la pension.\n";
    }
    $text .= 'Communication : ' . $rows[0]['pay_ref'] . "\n\n"
        . 'Prix estimé du séjour : ' . euros($total) . '. Le solde (' . euros($total - $deposit) . ') est à régler à la pension.';
    return $text;
}

/** Séjours d'un même groupe (même communication) pour un client. */
function pay_group(array $r): array
{
    if (empty($r['pay_ref'])) {
        return [$r];
    }
    return q_all('SELECT * FROM stay_requests WHERE user_id = ? AND pay_ref = ? ORDER BY id', [$r['user_id'], $r['pay_ref']]);
}

/**
 * La gérante coche ou décoche « acompte payé » / « totalité payée ».
 * Toute la demande (tous les chiens avec la même communication) suit, car il y a un seul virement.
 */
function set_payment(int $managerId, int $requestId, string $what, bool $paid): bool
{
    $r = q_one("SELECT * FROM stay_requests WHERE id = ? AND kind = 'sejour'", [$requestId]);
    if (!$r || !in_array($what, ['deposit', 'full'], true)) {
        return false;
    }
    $where = $r['pay_ref'] ? 'user_id = ? AND pay_ref = ?' : 'id = ?';
    $params = $r['pay_ref'] ? [$r['user_id'], $r['pay_ref']] : [$r['id']];
    if ($what === 'deposit') {
        q('UPDATE stay_requests SET deposit_paid_at = ' . ($paid ? 'COALESCE(deposit_paid_at, NOW())' : 'NULL') . ' WHERE ' . $where, $params);
    } else {
        q('UPDATE stay_requests SET paid_full_at = ' . ($paid ? 'COALESCE(paid_full_at, NOW()), deposit_paid_at = COALESCE(deposit_paid_at, NOW())' : 'NULL') . ' WHERE ' . $where, $params);
    }
    audit($managerId, 'payment_' . $what . ($paid ? '_paid' : '_unpaid'), (string) $requestId);
    return true;
}

/** État du paiement lisible : [classe, texte]. */
function payment_state(array $r): array
{
    if ($r['kind'] !== 'sejour' || $r['price_total'] === null) {
        return ['none', $r['kind'] === 'journee' ? 'Carte journée' : '—'];
    }
    if ($r['paid_full_at']) {
        return ['full', 'Tout est payé'];
    }
    if ($r['deposit_paid_at']) {
        return ['deposit', 'Acompte reçu'];
    }
    return ['due', 'Acompte à verser : ' . euros($r['deposit_amount'])];
}

/* ---------- Mise à jour automatique de la base (pas besoin de phpMyAdmin) ---------- */

const SCHEMA_VERSION = 2;

function ensure_schema(): void
{
    $flag = rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/schema-version.txt';
    if (is_file($flag) && (int) file_get_contents($flag) >= SCHEMA_VERSION) {
        return;
    }
    $have = array_column(q_all("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stay_requests'"), 'COLUMN_NAME');
    $add = [
        'price_total'     => 'DECIMAL(8,2) NULL',
        'deposit_amount'  => 'DECIMAL(8,2) NULL',
        'pay_ref'         => 'VARCHAR(24) NULL',
        'deposit_paid_at' => 'DATETIME NULL',
        'paid_full_at'    => 'DATETIME NULL',
        'created_by'      => 'INT UNSIGNED NULL',
    ];
    foreach ($add as $col => $def) {
        if (!in_array($col, $have, true)) {
            db()->exec('ALTER TABLE stay_requests ADD COLUMN ' . $col . ' ' . $def);
        }
    }
    // Séjours déjà enregistrés avant cette mise à jour : on calcule leur prix et leur acompte
    foreach (q_all("SELECT r.id, r.start_date, r.end_date, d.size FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.kind = 'sejour' AND r.price_total IS NULL") as $r) {
        $price = (float) estimate_stay($r['size'], $r['start_date'], $r['end_date'])[2];
        q('UPDATE stay_requests SET price_total = ?, deposit_amount = ?, pay_ref = COALESCE(pay_ref, ?) WHERE id = ?', [$price, deposit_for($price), structured_ref((int) $r['id']), $r['id']]);
    }
    @file_put_contents($flag, (string) SCHEMA_VERSION, LOCK_EX);
}

/**
 * Deux boutons à cocher pour la gérante : « Acompte payé » et « Tout payé ».
 * $back : la page où revenir après le clic (uniquement une page de l'espace gérante).
 */
function payment_toggles(array $r, string $back): string
{
    if ($r['kind'] !== 'sejour' || $r['price_total'] === null) {
        return '<span class="muted small">' . ($r['kind'] === 'journee' ? 'Carte journée' : '—') . '</span>';
    }
    $btn = static function (string $what, bool $on, string $label, string $amount): string {
        return '<button type="submit" name="set" value="' . $what . ':' . ($on ? '0' : '1') . '" class="pay__btn' . ($on ? ' is-on' : '') . '" aria-pressed="' . ($on ? 'true' : 'false') . '">'
            . '<span class="pay__box" aria-hidden="true"></span><span>' . h($label) . ' <small>' . h($amount) . '</small></span></button>';
    };
    return '<form method="post" action="/gerante/paiement.php" class="pay">' . csrf_field()
        . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="back" value="' . h($back) . '">'
        . $btn('deposit', (bool) $r['deposit_paid_at'], 'Acompte payé', euros($r['deposit_amount']))
        . $btn('full', (bool) $r['paid_full_at'], 'Tout payé', euros($r['price_total']))
        . ($r['pay_ref'] ? '<span class="pay__ref">' . h($r['pay_ref']) . '</span>' : '')
        . '</form>';
}

/** Adresse de retour sûre : seulement une page de l'espace gérante. */
function safe_gerante_back(string $back): string
{
    return preg_match('#^/gerante/[a-z\-]*(\.php)?(\?[A-Za-z0-9=&%_.\-+]*)?(\#[a-z\-]+)?$#', $back) ? $back : '/gerante/';
}
