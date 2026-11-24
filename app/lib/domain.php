<?php
if (!defined('CDM')) { http_response_code(404); exit; }

const MAX_STAY_DAYS = 90;

/* ---------- Chiens ---------- */

function user_dogs(int $userId): array
{
    return q_all('SELECT * FROM dogs WHERE user_id = ? ORDER BY name', [$userId]);
}

/** Un chien n'est rendu que s'il appartient bien à ce client. */
function find_own_dog(int $userId, int $dogId): ?array
{
    return q_one('SELECT * FROM dogs WHERE id = ? AND user_id = ?', [$dogId, $userId]);
}

/** Lit et vérifie le formulaire « fiche chien ». Retourne [données, erreurs]. */
function dog_from_post(): array
{
    $d = [
        'name'           => post('name', 80),
        'breed'          => post('breed', 120),
        'birth_date'     => post('birth_date', 10),
        'sex'            => post('sex', 10),
        'size'           => post('size', 10),
        'sterilized'     => post('sterilized', 1) === '1' ? 1 : 0,
        'vaccines_until' => post('vaccines_until', 10),
        'vet'            => post('vet', 190),
        'health'         => post('health', 2000),
        'temperament'    => post('temperament', 2000),
    ];
    $errors = [];
    if ($d['name'] === '') {
        $errors[] = 'Indiquez le nom du chien.';
    }
    if (!isset(SIZES[$d['size']])) {
        $errors[] = 'Choisissez le gabarit du chien.';
    }
    if (!in_array($d['sex'], ['', 'male', 'femelle'], true)) {
        $d['sex'] = '';
    }
    foreach (['birth_date', 'vaccines_until'] as $f) {
        if ($d[$f] === '') {
            $d[$f] = null;
        } elseif (!valid_date($d[$f])) {
            $errors[] = 'Une date est invalide.';
            $d[$f] = null;
        }
    }
    if ($d['birth_date'] && $d['birth_date'] > today()) {
        $errors[] = 'La date de naissance ne peut pas être dans le futur.';
    }
    return [$d, $errors];
}

function dog_age(?string $birth): string
{
    if (!$birth) {
        return '';
    }
    $diff = (new DateTimeImmutable($birth))->diff(new DateTimeImmutable('today'));
    if ($diff->y >= 1) {
        return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    }
    return max(1, $diff->m) . ' mois';
}

/* ---------- Cartes journée ---------- */

function card_total(array $c): int
{
    return (int) $c['paid_days'] + (int) $c['free_days'];
}

function card_remaining(array $c): int
{
    return card_total($c) - (int) $c['used_days'];
}

/**
 * Cartes encore valables (non expirées, au moins une journée restante).
 * Une carte ne sert que pour un chien de son gabarit : $size filtre par gabarit (null = toutes).
 */
function active_cards(int $userId, ?string $onDate = null, ?string $size = null): array
{
    $onDate ??= today();
    return q_all('SELECT * FROM cards WHERE user_id = ? AND expires_at >= ? AND used_days < paid_days + free_days AND (? IS NULL OR size = ?) ORDER BY expires_at, id', [$userId, $onDate, $size, $size]);
}

function remaining_days(int $userId, ?string $onDate = null, ?string $size = null): int
{
    $n = 0;
    foreach (active_cards($userId, $onDate, $size) as $c) {
        $n += card_remaining($c);
    }
    return $n;
}

/** Journées demandées (en attente), pour un gabarit de chien ou pour tous. */
function pending_day_requests(int $userId, ?string $size = null): int
{
    return (int) q("SELECT COUNT(*) FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.user_id = ? AND r.kind = 'journee' AND r.status = 'pending' AND (? IS NULL OR d.size = ?)", [$userId, $size, $size])->fetchColumn();
}

/** Journées encore utilisables par gabarit : ['petit' => 3, ...] (seulement les gabarits avec une carte). */
function available_days_by_size(int $userId): array
{
    $out = [];
    foreach (active_cards($userId) as $c) {
        $out[$c['size']] = ($out[$c['size']] ?? 0) + card_remaining($c);
    }
    foreach ($out as $size => $n) {
        $out[$size] = max(0, $n - pending_day_requests($userId, $size));
    }
    return $out;
}

/* ---------- Demandes de séjour ---------- */

/**
 * Crée une demande par chien. $items = [[dog_id, début, fin], ...].
 * Retourne la liste des erreurs (vide si tout est bon).
 */
function create_requests(array $user, string $kind, array $items, string $note): array
{
    $errors = [];
    if (!in_array($kind, ['sejour', 'journee'], true)) {
        return ['Type de garde inconnu.'];
    }
    if (!$items) {
        return ['Choisissez au moins un chien.'];
    }
    $uid = (int) $user['id'];
    $maxStart = (new DateTimeImmutable('today'))->modify('+1 year')->format('Y-m-d');
    $rows = [];
    foreach ($items as [$dogId, $from, $to]) {
        $dog = find_own_dog($uid, (int) $dogId);
        if (!$dog) {
            return ['Chien introuvable.'];
        }
        $from = valid_date((string) $from);
        $to = $kind === 'journee' ? $from : valid_date((string) $to);
        if (!$from || !$to) {
            $errors[] = 'Indiquez des dates valides pour ' . $dog['name'] . '.';
            continue;
        }
        if ($from < today()) {
            $errors[] = 'La date pour ' . $dog['name'] . ' est déjà passée.';
        } elseif ($from > $maxStart) {
            $errors[] = 'Les demandes sont possibles jusqu\'à un an à l\'avance.';
        }
        if ($kind === 'sejour') {
            $n = days_between($from, $to);
            if ($n < 1) {
                $errors[] = 'Pour ' . $dog['name'] . ', la date de départ doit être après la date d\'arrivée.';
            } elseif ($n > MAX_STAY_DAYS) {
                $errors[] = 'Un séjour ne peut pas dépasser ' . MAX_STAY_DAYS . ' jours. Contactez la pension pour un séjour plus long.';
            }
        }
        $overlap = q_one("SELECT id FROM stay_requests WHERE dog_id = ? AND status IN ('pending','confirmed') AND start_date <= ? AND end_date >= ?", [$dog['id'], $to, $from]);
        if ($overlap) {
            $errors[] = $dog['name'] . ' a déjà une demande sur ces dates.';
        }
        $rows[] = [$dog, $from, $to];
    }
    if ($kind === 'journee' && !$errors) {
        // Une carte « petit chien » ne sert que pour un petit chien, etc.
        $needed = [];
        foreach ($rows as [$dog, $from]) {
            $needed[$dog['size']][] = $dog['name'];
        }
        foreach ($needed as $size => $names) {
            $available = remaining_days($uid, $rows[0][1], $size) - pending_day_requests($uid, $size);
            if ($available < count($names)) {
                $label = mb_strtolower(size_label($size));
                $errors[] = $available <= 0
                    ? 'Pas de journée disponible sur une carte « ' . $label . ' » valable à cette date pour ' . implode(' et ', $names) . '. Contactez la pension pour acheter une carte de ce gabarit.'
                    : 'Il ne vous reste que ' . $available . ' journée(s) sur vos cartes « ' . $label . ' ».';
            }
        }
    }
    if ($errors) {
        return array_values(array_unique($errors));
    }

    db()->beginTransaction();
    foreach ($rows as [$dog, $from, $to]) {
        q('INSERT INTO stay_requests (user_id, dog_id, kind, start_date, end_date, note) VALUES (?, ?, ?, ?, ?, ?)', [$uid, $dog['id'], $kind, $from, $to, $note !== '' ? $note : null]);
    }
    db()->commit();
    audit($uid, 'request_created', $kind . ' x' . count($rows));

    $lines = array_map(static fn($r) => '- ' . $r[0]['name'] . ' : ' . ($kind === 'journee' ? 'journée du ' . date_fr($r[1]) : date_fr($r[1]) . ' → ' . date_fr($r[2])), $rows);
    send_mail((string) config('notify_email'), 'Nouvelle demande de ' . $user['first_name'] . ' ' . $user['last_name'],
        'Nouvelle demande (' . ($kind === 'journee' ? 'journée avec carte' : 'séjour') . ") :\n" . implode("\n", $lines)
        . ($note !== '' ? "\n\nMessage du client : " . $note : '') . "\n\nPour répondre : " . url('/gerante/'));
    return [];
}

/** Le client peut annuler une demande encore en attente. */
function client_cancel_request(int $userId, int $requestId): bool
{
    $done = q("UPDATE stay_requests SET status = 'cancelled', decided_at = NOW() WHERE id = ? AND user_id = ? AND status = 'pending'", [$requestId, $userId])->rowCount() === 1;
    if ($done) {
        audit($userId, 'request_cancelled_by_client', (string) $requestId);
    }
    return $done;
}

/**
 * Décision de la gérante : confirmed | refused | cancelled.
 * Une journée confirmée est déduite d'une carte ; refusée ou annulée ensuite, elle est rendue.
 * Retourne null si tout va bien, sinon un message d'erreur.
 */
function decide_request(int $managerId, int $requestId, string $decision, string $message): ?string
{
    if (!in_array($decision, ['confirmed', 'refused', 'cancelled'], true)) {
        return 'Décision inconnue.';
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = q_one('SELECT * FROM stay_requests WHERE id = ? FOR UPDATE', [$requestId]);
        if (!$r) {
            $pdo->rollBack();
            return 'Demande introuvable.';
        }
        $allowed = ['pending' => ['confirmed', 'refused'], 'confirmed' => ['cancelled']];
        if (!in_array($decision, $allowed[$r['status']] ?? [], true)) {
            $pdo->rollBack();
            return 'Cette demande a déjà été traitée.';
        }
        $cardId = $r['card_id'];
        if ($decision === 'confirmed' && $r['kind'] === 'journee') {
            $card = q_one('SELECT c.* FROM cards c JOIN dogs d ON d.id = ? AND d.size = c.size WHERE c.user_id = ? AND c.expires_at >= ? AND c.used_days < c.paid_days + c.free_days ORDER BY c.expires_at, c.id LIMIT 1 FOR UPDATE', [$r['dog_id'], $r['user_id'], $r['start_date']]);
            if (!$card) {
                $pdo->rollBack();
                return 'Ce client n\'a plus de journée disponible sur une carte de ce gabarit valable à cette date.';
            }
            q('UPDATE cards SET used_days = used_days + 1 WHERE id = ?', [$card['id']]);
            $cardId = $card['id'];
        }
        if ($decision === 'cancelled' && $r['card_id']) {
            q('UPDATE cards SET used_days = GREATEST(used_days - 1, 0) WHERE id = ?', [$r['card_id']]);
            $cardId = null;
        }
        q('UPDATE stay_requests SET status = ?, manager_message = ?, card_id = ?, decided_at = NOW() WHERE id = ?', [$decision, $message !== '' ? $message : null, $cardId, $requestId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    audit($managerId, 'request_' . $decision, (string) $requestId);

    $info = q_one('SELECT u.email, u.first_name, d.name AS dog FROM stay_requests r JOIN users u ON u.id = r.user_id JOIN dogs d ON d.id = r.dog_id WHERE r.id = ?', [$requestId]);
    $what = $r['kind'] === 'journee' ? 'la journée du ' . date_fr($r['start_date']) : 'le séjour du ' . date_fr($r['start_date']) . ' au ' . date_fr($r['end_date']);
    $verb = ['confirmed' => 'est confirmé(e)', 'refused' => 'n\'a malheureusement pas pu être accepté(e)', 'cancelled' => 'a été annulé(e)'][$decision];
    send_mail($info['email'], 'Votre demande pour ' . $info['dog'] . ' : ' . mb_strtolower(STATUS_LABELS[$decision]),
        greet($info) . 'Votre demande pour ' . $info['dog'] . ' (' . $what . ') ' . $verb . '.'
        . ($message !== '' ? "\n\nMessage de la pension : " . $message : '')
        . "\n\nVoir vos réservations : " . url('/espace/reservations.php'));
    return null;
}

function request_label(array $r): string
{
    return $r['kind'] === 'journee'
        ? date_fr($r['start_date'])
        : date_fr($r['start_date'], false) . ' → ' . date_fr($r['end_date']);
}

/* ---------- Contrats ---------- */

function contracts_dir(): string
{
    $dir = rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/contrats';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

function contract_path(array $c): string
{
    // Le nom stocké est 64 caractères hexadécimaux choisis par le serveur : aucun chemin ne peut être détourné.
    if (!preg_match('/^[a-f0-9]{64}$/', $c['stored_name'])) {
        throw new RuntimeException('Nom de fichier invalide');
    }
    return contracts_dir() . '/' . $c['stored_name'] . '.pdf';
}

const CONTRACT_MAX_BYTES = 10 * 1024 * 1024;

/** Enregistre un PDF envoyé par la gérante. Retourne null ou un message d'erreur. */
function store_contract(int $managerId, int $userId, string $title, array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return 'Le fichier n\'a pas pu être reçu.';
    }
    if ($file['size'] > CONTRACT_MAX_BYTES) {
        return 'Le fichier dépasse 10 Mo.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') {
        return 'Seuls les fichiers PDF sont acceptés.';
    }
    if (!q_one("SELECT id FROM users WHERE id = ? AND role = 'client'", [$userId])) {
        return 'Client introuvable.';
    }
    $title = $title !== '' ? $title : 'Contrat du ' . date_fr(today());
    $stored = bin2hex(random_bytes(32));
    $dest = contracts_dir() . '/' . $stored . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return 'Le fichier n\'a pas pu être enregistré.';
    }
    chmod($dest, 0640);
    q('INSERT INTO contracts (user_id, title, stored_name, size_bytes) VALUES (?, ?, ?, ?)', [$userId, $title, $stored, (int) $file['size']]);
    audit($managerId, 'contract_uploaded', 'client ' . $userId);
    $u = q_one('SELECT email, first_name FROM users WHERE id = ?', [$userId]);
    send_mail($u['email'], 'Un nouveau document est disponible', greet($u) . 'La pension a déposé un document dans votre espace client : « ' . $title . " ».\n\nPour le consulter : " . url('/espace/reservations.php#contrats'));
    return null;
}

function send_contract_file(array $c): never
{
    $path = contract_path($c);
    if (!is_file($path)) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $safe = preg_replace('/[^A-Za-z0-9 _.-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $c['title']) ?: 'contrat') ?: 'contrat';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . trim($safe) . '.pdf"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/** Suppression complète d'un compte client (droit à l'effacement RGPD). */
function delete_user_account(int $userId): void
{
    foreach (q_all('SELECT * FROM contracts WHERE user_id = ?', [$userId]) as $c) {
        @unlink(contract_path($c));
    }
    q('DELETE FROM users WHERE id = ? AND role = ?', [$userId, 'client']);
    audit(null, 'account_deleted', 'user ' . $userId);
}
