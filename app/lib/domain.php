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
    return q_all("SELECT * FROM cards WHERE status = 'active' AND user_id = ? AND expires_at >= ? AND used_days < paid_days + free_days AND (? IS NULL OR size = ?) ORDER BY expires_at, id", [$userId, $onDate, $size, $size]);
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
function create_requests(array $user, string $kind, array $items, string $note, ?int $managerId = null, array &$createdIds = []): array
{
    $createdIds = [];
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
        if ($from < today() && $managerId === null) {
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
        $price = $deposit = null;
        if ($kind === 'sejour') {
            $price = (float) estimate_stay($dog['size'], $from, $to)[2];
            $deposit = deposit_for($price);
        }
        q('INSERT INTO stay_requests (user_id, dog_id, kind, start_date, end_date, note, price_total, deposit_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$uid, $dog['id'], $kind, $from, $to, $note !== '' ? $note : null, $price, $deposit, $managerId]);
        $createdIds[] = (int) db()->lastInsertId();
    }
    if ($kind === 'sejour') {
        // Une seule communication pour toute la demande (un seul virement, même avec plusieurs chiens)
        $in = implode(',', array_fill(0, count($createdIds), '?'));
        q('UPDATE stay_requests SET pay_ref = ? WHERE id IN (' . $in . ')', array_merge([structured_ref($createdIds[0])], $createdIds));
    }
    db()->commit();
    audit($managerId ?? $uid, $managerId ? 'request_created_by_manager' : 'request_created', $kind . ' x' . count($rows));
    if ($managerId !== null) {
        return []; // la page de la gérante confirme et prévient le client elle-même
    }

    $lines = array_map(static fn($r) => '- ' . $r[0]['name'] . ' : ' . ($kind === 'journee' ? 'journée du ' . date_fr($r[1]) : date_fr($r[1]) . ' → ' . date_fr($r[2])), $rows);
    send_mail((string) config('notify_email'), 'Nouvelle demande de ' . $user['first_name'] . ' ' . $user['last_name'],
        'Nouvelle demande (' . ($kind === 'journee' ? 'journée avec carte' : 'séjour') . ") :\n" . implode("\n", $lines)
        . ($note !== '' ? "\n\nMessage du client : " . $note : '') . "\n\nPour répondre : " . url('/gerante/'));

    // Accusé de réception au client (l'acompte n'est demandé qu'après la confirmation de la pension)
    send_mail((string) $user['email'], 'Nous avons bien reçu votre demande', greet($user)
        . 'Merci ! Nous avons bien reçu votre demande :' . "\n" . implode("\n", $lines) . "\n\n"
        . 'La pension vous confirme chaque chien par e-mail.'
        . ($kind === 'sejour' ? ' Une fois le séjour confirmé, vous recevrez le montant de l\'acompte à verser et le numéro de compte.' : '')
        . "\n\nVoir vos réservations : " . url('/espace/reservations.php'));
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
function decide_request(int $managerId, int $requestId, string $decision, string $message, bool $notify = true): ?string
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
            $card = q_one('SELECT c.* FROM cards c JOIN dogs d ON d.id = ? AND d.size = c.size WHERE c.status = \'active\' AND c.user_id = ? AND c.expires_at >= ? AND c.used_days < c.paid_days + c.free_days ORDER BY c.expires_at, c.id LIMIT 1 FOR UPDATE', [$r['dog_id'], $r['user_id'], $r['start_date']]);
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
    if (!$notify) {
        return null;
    }

    $info = q_one('SELECT u.email, u.first_name, d.name AS dog FROM stay_requests r JOIN users u ON u.id = r.user_id JOIN dogs d ON d.id = r.dog_id WHERE r.id = ?', [$requestId]);
    $what = $r['kind'] === 'journee' ? 'la journée du ' . date_fr($r['start_date']) : 'le séjour du ' . date_fr($r['start_date']) . ' au ' . date_fr($r['end_date']);
    $verb = ['confirmed' => 'est confirmé(e)', 'refused' => 'n\'a malheureusement pas pu être accepté(e)', 'cancelled' => 'a été annulé(e)'][$decision];
    send_mail($info['email'], 'Votre demande pour ' . $info['dog'] . ' : ' . mb_strtolower(STATUS_LABELS[$decision]),
        greet($info) . 'Votre demande pour ' . $info['dog'] . ' (' . $what . ') ' . $verb . '.'
        . ($message !== '' ? "\n\nMessage de la pension : " . $message : '')
        . ($decision === 'confirmed' ? deposit_reminder($requestId) : '')
        . "\n\nVoir vos réservations : " . url('/espace/reservations.php'));
    return null;
}

/** Rappel de l'acompte dans l'e-mail de confirmation, s'il n'est pas encore reçu. */
function deposit_reminder(int $requestId): string
{
    $r = q_one('SELECT * FROM stay_requests WHERE id = ?', [$requestId]);
    if (!$r || $r['kind'] !== 'sejour' || $r['deposit_paid_at'] || $r['paid_full_at'] || (float) $r['deposit_amount'] <= 0) {
        return '';
    }
    $text = deposit_text([$r]);
    return $text !== '' ? "\n\n" . $text : '';
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
    return contracts_dir() . '/' . $c['stored_name'] . '.' . (($c['ext'] ?? 'pdf') === 'jpg' ? 'jpg' : 'pdf');
}

const CONTRACT_MAX_BYTES = 10 * 1024 * 1024;

/**
 * Enregistre un contrat envoyé par la gérante : un PDF, ou une photo prise avec le téléphone.
 * $requestId : la réservation à laquelle il se rapporte (facultatif).
 * Retourne null ou un message d'erreur.
 */
function store_contract(int $managerId, int $userId, string $title, array $file, ?int $requestId = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return 'Le fichier n\'a pas pu être reçu.';
    }
    if (!q_one("SELECT id FROM users WHERE id = ? AND role = 'client'", [$userId])) {
        return 'Client introuvable.';
    }
    if ($requestId && !q_one('SELECT id FROM stay_requests WHERE id = ? AND user_id = ?', [$requestId, $userId])) {
        return 'Réservation introuvable pour ce client.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $stored = bin2hex(random_bytes(32));
    if (str_starts_with((string) $mime, 'image/')) {
        $ext = 'jpg';
        $dest = contracts_dir() . '/' . $stored . '.jpg';
        $err = save_image_upload($file, $dest, 2200);
        if ($err) {
            return $err;
        }
    } else {
        if ($file['size'] > CONTRACT_MAX_BYTES) {
            return 'Le fichier dépasse 10 Mo.';
        }
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $head !== '%PDF-') {
            return 'Seuls les PDF et les photos (JPEG, PNG) sont acceptés.';
        }
        $ext = 'pdf';
        $dest = contracts_dir() . '/' . $stored . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return 'Le fichier n\'a pas pu être enregistré.';
        }
        chmod($dest, 0640);
    }
    $title = $title !== '' ? $title : 'Contrat du ' . date_fr(today());
    q('INSERT INTO contracts (user_id, title, stored_name, size_bytes, request_id, ext) VALUES (?, ?, ?, ?, ?, ?)', [$userId, $title, $stored, (int) filesize($dest), $requestId ?: null, $ext]);
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
    $isPhoto = ($c['ext'] ?? 'pdf') === 'jpg';
    header('Content-Type: ' . ($isPhoto ? 'image/jpeg' : 'application/pdf'));
    header('Content-Disposition: ' . ($isPhoto ? 'inline' : 'attachment') . '; filename="' . trim($safe) . ($isPhoto ? '.jpg' : '.pdf') . '"');
    header('Cache-Control: private, no-store');
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
    foreach (q_all('SELECT photo FROM dogs WHERE user_id = ? AND photo IS NOT NULL', [$userId]) as $d) {
        @unlink(dog_photo_path($d['photo']));
    }
    archive_for_accounting($userId);
    q('DELETE FROM users WHERE id = ? AND role = ?', [$userId, 'client']);
    audit(null, 'account_deleted', 'user ' . $userId);
}

/* ---------- Achat de cartes journée par le client ---------- */

const MAX_PENDING_CARD_REQUESTS = 3;

/** Prix d'une carte (10 ou 20) pour un gabarit, ou null si pas encore fixé. */
function card_price(string $size, int $type): ?float
{
    $p = tarif_for($size)['prixCarte' . $type] ?? null;
    return $p === null || $p === '' ? null : (float) $p;
}

/** Texte « comment payer » pour une carte demandée. */
function card_payment_text(array $card): string
{
    $p = load_payment();
    $text = 'Montant à verser : ' . euros($card['price_paid']) . "\n";
    if ($p['iban'] !== '') {
        $text .= 'Sur le compte : ' . format_iban($p['iban']) . ($p['titulaire'] !== '' ? ' (' . $p['titulaire'] . ')' : '') . "\n"
            . ($p['bic'] !== '' ? 'BIC : ' . $p['bic'] . "\n" : '');
    } else {
        $text .= "Le numéro de compte vous sera communiqué par la pension.\n";
    }
    return $text . 'Merci d\'indiquer votre nom et « Carte ' . (int) $card['paid_days'] . ' » dans le virement. Vous pouvez aussi payer sur place.';
}

/** Le client demande une carte. Retourne [erreur|null, id de la carte]. */
function request_card(array $user, string $size, int $type): array
{
    if (!isset(SIZES[$size]) || !in_array($type, [10, 20], true)) {
        return ['Choisissez une carte.', 0];
    }
    $price = card_price($size, $type);
    if ($price === null) {
        return ['Le prix de cette carte n\'est pas encore fixé. Contactez la pension.', 0];
    }
    $uid = (int) $user['id'];
    $pending = (int) q("SELECT COUNT(*) FROM cards WHERE user_id = ? AND status = 'requested'", [$uid])->fetchColumn();
    if ($pending >= MAX_PENDING_CARD_REQUESTS) {
        return ['Vous avez déjà ' . $pending . ' cartes en attente. Attendez qu\'elles soient activées, ou contactez la pension.', 0];
    }
    $t = load_tarifs();
    $free = (int) $t['cartes']['offertes' . $type];
    $expires = (new DateTimeImmutable('today'))->modify('+' . (int) $t['cartes']['validiteMois'] . ' months')->format('Y-m-d');
    q("INSERT INTO cards (user_id, paid_days, free_days, size, price_paid, purchased_at, expires_at, status, requested_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'requested', NOW())",
        [$uid, $type, $free, $size, $price, today(), $expires]);
    $id = (int) db()->lastInsertId();
    audit($uid, 'card_requested', 'carte ' . $type . ' ' . $size);
    $card = q_one('SELECT * FROM cards WHERE id = ?', [$id]);
    $label = 'Carte ' . $type . ' journées (+ ' . $free . ' offerte' . ($free > 1 ? 's' : '') . ') · ' . mb_strtolower(size_label($size));
    send_mail((string) $user['email'], 'Votre demande de carte journée', greet($user)
        . 'Merci ! Nous avons bien reçu votre demande : ' . $label . ".\n\n"
        . card_payment_text($card) . "\n\n"
        . 'La pension active votre carte dès que possible ; vous recevrez un e-mail à ce moment-là.'
        . "\n\nVoir mes cartes : " . url('/espace/carte.php'));
    send_mail((string) config('notify_email'), 'Achat de carte : ' . $user['first_name'] . ' ' . $user['last_name'],
        'Nouvelle demande d\'achat de carte : ' . $label . ' — ' . euros($price) . ".\n\n"
        . 'Client : ' . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ")\n\n"
        . 'Pour activer la carte (avant ou après le paiement) : ' . url('/gerante/cartes.php'));
    return [null, $id];
}

/** Le client annule sa demande de carte tant qu'elle n'est pas activée. */
function client_cancel_card(int $userId, int $cardId): bool
{
    $done = q("DELETE FROM cards WHERE id = ? AND user_id = ? AND status = 'requested'", [$cardId, $userId])->rowCount() === 1;
    if ($done) {
        audit($userId, 'card_request_cancelled', (string) $cardId);
    }
    return $done;
}

/**
 * La gérante active une carte demandée (avant ou après avoir reçu le paiement),
 * ou la refuse. La validité commence le jour de l'activation.
 */
function decide_card(int $managerId, int $cardId, string $decision, bool $paid = false): ?string
{
    $c = q_one("SELECT c.*, u.email, u.first_name FROM cards c JOIN users u ON u.id = c.user_id WHERE c.id = ? AND c.status = 'requested'", [$cardId]);
    if (!$c) {
        return 'Cette demande de carte a déjà été traitée.';
    }
    if ($decision === 'refuse') {
        q('DELETE FROM cards WHERE id = ?', [$cardId]);
        audit($managerId, 'card_request_refused', (string) $cardId);
        send_mail($c['email'], 'Votre demande de carte journée', greet($c)
            . 'Votre demande de carte ' . (int) $c['paid_days'] . ' journées n\'a pas pu être acceptée. Contactez la pension pour en savoir plus.');
        return null;
    }
    $months = (int) load_tarifs()['cartes']['validiteMois'];
    $expires = (new DateTimeImmutable('today'))->modify('+' . $months . ' months')->format('Y-m-d');
    q("UPDATE cards SET status = 'active', purchased_at = ?, expires_at = ?, paid_at = " . ($paid ? 'NOW()' : 'NULL') . ' WHERE id = ?', [today(), $expires, $cardId]);
    audit($managerId, 'card_activated', 'carte ' . $cardId . ($paid ? ' payée' : ' non payée'));
    $free = (int) $c['free_days'];
    send_mail($c['email'], 'Votre carte journée est activée', greet($c)
        . 'Bonne nouvelle : votre carte ' . (int) $c['paid_days'] . ' journées (+ ' . $free . ' offerte' . ($free > 1 ? 's' : '') . ') est active jusqu\'au ' . date_fr($expires) . ".\n\n"
        . ($paid ? '' : "Si ce n'est pas encore fait, pensez à régler la carte :\n" . card_payment_text($c) . "\n\n")
        . 'Pour réserver une journée : ' . url('/espace/demande.php?kind=journee&size=' . $c['size']) . "\nPour voir votre carte : " . url('/espace/carte.php'));
    return null;
}

/** La gérante coche ou décoche « carte payée ». */
function set_card_paid(int $managerId, int $cardId, bool $paid): bool
{
    $done = q('UPDATE cards SET paid_at = ' . ($paid ? 'COALESCE(paid_at, NOW())' : 'NULL') . " WHERE id = ? AND status = 'active'", [$cardId])->rowCount() >= 0;
    audit($managerId, $paid ? 'card_paid' : 'card_unpaid', (string) $cardId);
    return $done;
}
