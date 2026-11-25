<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/** Échappe un texte avant de l'afficher dans une page (protection contre l'injection de code « XSS »). */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    // Seulement des adresses internes au site : jamais de redirection vers un autre site.
    if (preg_match('#^(https?:)?//#i', $to) || str_contains($to, "\n") || str_contains($to, "\r")) {
        $to = '/espace/';
    }
    header('Location: ' . $to, true, 303);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function post(string $key, int $maxLen = 2000): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = trim(str_replace("\0", '', $v));
    return mb_substr($v, 0, $maxLen);
}

function query_int(string $key): int
{
    $v = $_GET[$key] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int) $v : 0;
}

function post_int(string $key): int
{
    $v = $_POST[$key] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int) $v : 0;
}

/** Date au format AAAA-MM-JJ valide, sinon null. */
function valid_date(string $s): ?string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

function today(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

function days_between(string $from, string $to): int
{
    return (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->format('%r%a');
}

function date_fr(?string $iso, bool $withYear = true): string
{
    if (!$iso) {
        return '';
    }
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $d = new DateTimeImmutable($iso);
    return $d->format('j') . ' ' . $mois[(int) $d->format('n') - 1] . ($withYear ? ' ' . $d->format('Y') : '');
}

function euros($n): string
{
    $n = (float) $n;
    return (floor($n) == $n ? number_format($n, 0, ',', ' ') : number_format($n, 2, ',', ' ')) . ' €';
}

function client_ip(): string
{
    // REMOTE_ADDR uniquement : les en-têtes « X-Forwarded-For » peuvent être falsifiés.
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

const SIZES = ['petit' => 'Petit chien', 'moyen' => 'Chien moyen', 'grand' => 'Grand chien'];

function size_label(string $size): string
{
    return SIZES[$size] ?? $size;
}

const STATUS_LABELS = [
    'pending'   => 'En attente',
    'confirmed' => 'Confirmé',
    'refused'   => 'Refusé',
    'cancelled' => 'Annulé',
];
