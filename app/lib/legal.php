<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/*
 * Informations légales de l'entreprise et règles des conditions générales.
 * Modifiables par la gérante (page « Infos légales »), gardées dans app/storage/entreprise.json.
 * Elles alimentent les pages Mentions légales, Conditions générales et Vos données.
 */

const DEFAULT_LEGAL = [
    'nom_commercial'   => 'Coeur de Meute',
    'nom_legal'        => '',          // nom de la personne ou de la société
    'forme'            => '',          // ex. : personne physique, SRL…
    'adresse'          => 'Chaussée de Wavre 28, 4520 Wanze, Belgique',
    'email'            => 'coeurdemeute1@gmail.com',
    'telephone'        => '0455 10 97 97',
    'bce'              => '',          // numéro d'entreprise, ex. 0123.456.789
    'tva'              => 'franchise', // 'franchise' (pas de TVA) ou 'assujetti'
    'agrement'         => '',          // numéro d'agrément bien-être animal
    'acompte_delai'    => 7,           // jours pour payer l'acompte après la confirmation
    'annulation_jours' => 30,          // acompte remboursé si annulation au moins X jours avant l'arrivée
    'carte_rembourse'  => 1,           // carte non utilisée remboursée dans les 14 jours
    'regles_accueil'   => '',          // règles en plus, écrites par la gérante
    'cgv_maj'          => '2026-11-25',// date de la dernière modification des conditions
];

function legal_file(): string
{
    return rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/entreprise.json';
}

function load_legal(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $l = DEFAULT_LEGAL;
    $raw = is_file(legal_file()) ? json_decode((string) file_get_contents(legal_file()), true) : null;
    if (is_array($raw)) {
        $l = array_merge($l, array_intersect_key($raw, $l));
    }
    $l['acompte_delai'] = max(1, min(60, (int) $l['acompte_delai']));
    $l['annulation_jours'] = max(0, min(180, (int) $l['annulation_jours']));
    $l['carte_rembourse'] = (int) (bool) $l['carte_rembourse'];
    $l['tva'] = $l['tva'] === 'assujetti' ? 'assujetti' : 'franchise';
    return $cache = $l;
}

function save_legal(array $l): bool
{
    $file = legal_file();
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0750, true);
    }
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, json_encode($l, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) {
        return false;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** Numéro BCE au format 0123.456.789, ou null s'il n'est pas valide (contrôle modulo 97 belge). */
function clean_bce(string $raw): ?string
{
    $d = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($d) === 9) {
        $d = '0' . $d;
    }
    if (!preg_match('/^[01]\d{9}$/', $d) || 97 - ((int) substr($d, 0, 8) % 97) !== (int) substr($d, 8, 2)) {
        return null;
    }
    return substr($d, 0, 4) . '.' . substr($d, 4, 3) . '.' . substr($d, 7, 3);
}

/** Ce qu'il manque encore pour que les pages légales soient complètes (pour la gérante). */
function legal_missing(): array
{
    $l = load_legal();
    $miss = [];
    foreach (['nom_legal' => 'le nom légal', 'bce' => 'le numéro d\'entreprise (BCE)', 'agrement' => 'le numéro d\'agrément'] as $k => $label) {
        if (trim((string) $l[$k]) === '') {
            $miss[] = $label;
        }
    }
    return $miss;
}

/** Version des conditions générales : change dès que la gérante les modifie. */
function terms_version(): string
{
    return (string) load_legal()['cgv_maj'];
}

/** Case « J'accepte les conditions générales » (obligatoire). */
function terms_checkbox(): string
{
    return '<label class="check terms"><input type="checkbox" name="cgv" value="1" required>'
        . '<span>J\'ai lu et j\'accepte les <a href="/espace/conditions.php" target="_blank" rel="noopener">conditions générales</a>'
        . ' (annulation, acompte, conditions d\'accueil).</span></label>';
}

/** Vérifie la case et garde la trace de l'accord. Retourne un message d'erreur, ou null. */
function accept_terms(int $userId): ?string
{
    if (post('cgv', 3) !== '1') {
        return 'Merci de cocher la case pour accepter les conditions générales.';
    }
    q('UPDATE users SET terms_accepted_at = NOW(), terms_version = ? WHERE id = ?', [terms_version(), $userId]);
    return null;
}

/**
 * Garde les données nécessaires à la comptabilité avant la suppression d'un compte :
 * nom, chien, dates, montants et dates de paiement (pas de téléphone, d'adresse e-mail ni de fiche santé).
 * Effacées automatiquement après ACCOUNTING_YEARS ans.
 */
const ACCOUNTING_YEARS = 10;

function archive_for_accounting(int $userId): void
{
    $u = q_one('SELECT first_name, last_name, address FROM users WHERE id = ?', [$userId]);
    if (!$u) {
        return;
    }
    $client = trim($u['first_name'] . ' ' . $u['last_name']);
    $rows = q_all("SELECT r.kind, r.start_date, r.end_date, r.status, r.price_total, r.deposit_amount, r.deposit_paid_at, r.paid_full_at, d.name AS dog
        FROM stay_requests r JOIN dogs d ON d.id = r.dog_id
        WHERE r.user_id = ? AND r.status = 'confirmed'", [$userId]);
    foreach ($rows as $r) {
        if ($r['price_total'] === null && $r['deposit_paid_at'] === null && $r['paid_full_at'] === null) {
            continue;
        }
        q('INSERT INTO accounting_archive (client, address, kind, label, start_date, end_date, amount, deposit, deposit_paid_at, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $client, $u['address'], $r['kind'], $r['dog'], $r['start_date'], $r['end_date'],
            $r['price_total'], $r['deposit_amount'], $r['deposit_paid_at'], $r['paid_full_at'],
        ]);
    }
    foreach (q_all("SELECT paid_days, size, price_paid, purchased_at, expires_at, paid_at FROM cards WHERE user_id = ? AND (status = 'active' OR paid_at IS NOT NULL)", [$userId]) as $c) {
        q('INSERT INTO accounting_archive (client, address, kind, label, start_date, end_date, amount, deposit, deposit_paid_at, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?)', [
            $client, $u['address'], 'carte', 'Carte ' . (int) $c['paid_days'] . ' (' . $c['size'] . ')', $c['purchased_at'], $c['expires_at'], $c['price_paid'], $c['paid_at'],
        ]);
    }
}

/** Bloc « qui sommes-nous » (nom, adresse, BCE…) en HTML, sans les champs encore vides. */
function legal_identity_html(): string
{
    $l = load_legal();
    $lines = [];
    $name = $l['nom_legal'] !== '' ? $l['nom_legal'] . ($l['forme'] !== '' ? ' (' . $l['forme'] . ')' : '') . ', sous le nom « ' . $l['nom_commercial'] . ' »' : $l['nom_commercial'];
    $lines[] = '<strong>' . h($name) . '</strong>';
    if ($l['adresse'] !== '') {
        $lines[] = h($l['adresse']);
    }
    if ($l['bce'] !== '') {
        $lines[] = 'Numéro d\'entreprise (BCE) : ' . h($l['bce']);
        $lines[] = $l['tva'] === 'assujetti' ? 'TVA : BE ' . h($l['bce']) : 'TVA : non applicable (régime de la franchise des petites entreprises)';
    }
    if ($l['agrement'] !== '') {
        $lines[] = 'Agrément bien-être animal (Région wallonne) : ' . h($l['agrement']);
    }
    if ($l['email'] !== '') {
        $lines[] = 'E-mail : <a href="mailto:' . h($l['email']) . '">' . h($l['email']) . '</a>';
    }
    if ($l['telephone'] !== '') {
        $lines[] = 'Téléphone : ' . h($l['telephone']);
    }
    return '<p class="legal-id">' . implode('<br>', $lines) . '</p>';
}

function legal_date(string $iso): string
{
    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $t = strtotime($iso) ?: time();
    return (int) date('j', $t) . ' ' . $mois[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}
