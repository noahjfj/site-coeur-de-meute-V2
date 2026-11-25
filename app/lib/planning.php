<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/*
 * Planning de la pension et lien d'agenda pour Google Agenda (format iCalendar).
 * Le lien contient une clé secrète : seule la personne qui l'a peut lire l'agenda.
 */

function agenda_token_file(): string
{
    return rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/agenda-cle.txt';
}

function agenda_token(bool $renew = false): string
{
    $f = agenda_token_file();
    $t = is_file($f) ? trim((string) file_get_contents($f)) : '';
    if ($renew || !preg_match('/^[a-f0-9]{48}$/', $t)) {
        $t = bin2hex(random_bytes(24));
        if (!is_dir(dirname($f))) {
            mkdir(dirname($f), 0750, true);
        }
        file_put_contents($f, $t, LOCK_EX);
        @chmod($f, 0640);
    }
    return $t;
}

function agenda_url(): string
{
    return url('/gerante/agenda.php?cle=' . agenda_token());
}

/** Réservations (confirmées, et en attente si demandé) qui touchent la période. */
function planning_rows(string $from, string $to, bool $withPending = true): array
{
    $statuses = $withPending ? "('confirmed','pending')" : "('confirmed')";
    return q_all('SELECT r.*, d.name AS dog, d.size, u.first_name, u.last_name, u.phone, u.id AS uid FROM stay_requests r
        JOIN dogs d ON d.id = r.dog_id JOIN users u ON u.id = r.user_id
        WHERE r.status IN ' . $statuses . ' AND r.start_date <= ? AND r.end_date >= ? ORDER BY r.start_date, d.name', [$to, $from]);
}

/** Texte sûr pour un fichier iCalendar (échappe , ; \ et retours à la ligne, coupe à 75 octets). */
function ics_text(string $s): string
{
    $s = str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\\,', '\\n', '\\n', ''], $s);
    return $s;
}

function ics_line(string $line): string
{
    $out = '';
    while (strlen($line) > 74) {
        $cut = 74;
        while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
            $cut--; // ne pas couper au milieu d'une lettre accentuée
        }
        $out .= substr($line, 0, $cut) . "\r\n ";
        $line = substr($line, $cut);
    }
    return $out . $line . "\r\n";
}

function planning_ics(): string
{
    $from = (new DateTimeImmutable('today'))->modify('-3 months')->format('Y-m-d');
    $to = (new DateTimeImmutable('today'))->modify('+18 months')->format('Y-m-d');
    $host = parse_url((string) config('base_url'), PHP_URL_HOST) ?: 'coeurdemeute';
    $now = gmdate('Ymd\THis\Z');
    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Coeur de Meute//Planning//FR\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n"
        . ics_line('X-WR-CALNAME:' . ics_text('Coeur de Meute – pension'))
        . "X-WR-TIMEZONE:Europe/Brussels\r\nREFRESH-INTERVAL;VALUE=DURATION:PT1H\r\nX-PUBLISHED-TTL:PT1H\r\n";
    foreach (planning_rows($from, $to, false) as $r) {
        $start = str_replace('-', '', $r['start_date']);
        $end = (new DateTimeImmutable($r['end_date']))->modify('+1 day')->format('Ymd'); // le jour de départ est compris
        $what = $r['kind'] === 'journee' ? 'Journée' : 'Séjour';
        $summary = '🐾 ' . $r['dog'] . ' (' . mb_strtolower(size_label($r['size'])) . ') · ' . $r['first_name'] . ' ' . $r['last_name'];
        $desc = $what . ($r['kind'] === 'sejour' ? ' du ' . date_fr($r['start_date']) . ' au ' . date_fr($r['end_date']) : ' du ' . date_fr($r['start_date']))
            . ($r['phone'] ? "\nTéléphone : " . $r['phone'] : '')
            . ($r['note'] ? "\nMessage du client : " . $r['note'] : '')
            . "\nFiche : " . url('/gerante/clients.php?id=' . (int) $r['uid']);
        $ics .= "BEGIN:VEVENT\r\n"
            . 'UID:sejour-' . (int) $r['id'] . '@' . $host . "\r\n"
            . 'DTSTAMP:' . $now . "\r\n"
            . 'DTSTART;VALUE=DATE:' . $start . "\r\n"
            . 'DTEND;VALUE=DATE:' . $end . "\r\n"
            . ics_line('SUMMARY:' . ics_text($summary))
            . ics_line('DESCRIPTION:' . ics_text($desc))
            . "TRANSP:TRANSPARENT\r\nEND:VEVENT\r\n";
    }
    return $ics . "END:VCALENDAR\r\n";
}
