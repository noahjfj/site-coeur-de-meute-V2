<?php
/*
 * Fonctions communes : sécurité, fichiers, messages.
 */
if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }

// Les fichiers privés commencent par cette ligne PHP : ouverts depuis le web, ils renvoient une erreur 404.
// Ils sont lus comme du texte (jamais inclus), pour ne pas être servis depuis le cache OPcache.
const GUARD = "<?php http_response_code(404); exit; ?>\n";

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function to_number(string $value): float
{
    return (float) str_replace(',', '.', trim($value));
}

// ---------- Fichiers ----------
function write_atomic(string $file, string $content): bool
{
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function read_private(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode(substr((string) file_get_contents($file), strlen(GUARD)), true);
    return is_array($data) ? $data : null;
}

function write_private(string $file, array $data): bool
{
    return write_atomic($file, GUARD . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
}

function read_json(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function write_json(string $file, array $data): bool
{
    return write_atomic($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
}

// ---------- Session, formulaires, messages ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    $list = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $list;
}

function admin_url(string $page = '', array $params = []): string
{
    $params = $page ? ['page' => $page] + $params : $params;
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

function redirect_to(string $page = '', array $params = [], string $anchor = ''): void
{
    header('Location: ' . admin_url($page, $params) . ($anchor ? '#' . $anchor : ''), true, 303);
    exit;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

// ---------- Accès ----------
function load_access(): ?array
{
    $data = read_private(FILE_ACCESS);
    return ($data && isset($data['user'], $data['hash'])) ? $data : null;
}

function save_access(string $user, string $password): bool
{
    return write_private(FILE_ACCESS, ['user' => $user, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin']) && ($_SESSION['login_time'] ?? 0) > time() - SESSION_HOURS * 3600;
}

function log_in(): void
{
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['login_time'] = time();
}

/*
 * Code d'installation : nécessaire pour créer l'accès à la première visite.
 * Il est écrit dans admin/code-installation.php, que seule une personne ayant accès
 * à l'hébergement (gestionnaire de fichiers ou FTP) peut ouvrir.
 */
function setup_code(): string
{
    $existing = read_setup_code();
    if ($existing !== '') {
        return $existing;
    }
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $content = GUARD
        . json_encode(['code' => $code])
        . "\n\nCODE D'INSTALLATION DE L'ESPACE ADMINISTRATEUR : {$code}\n"
        . "Saisissez ce code sur la page /admin/ pour créer votre identifiant et votre mot de passe.\n"
        . "Ce fichier est supprimé automatiquement une fois l'accès créé.\n";
    if (!write_atomic(FILE_SETUP, $content)) {
        return '';
    }
    return $code;
}

/** Le fichier contient la ligne de protection, le code en JSON, puis une explication lisible. */
function read_setup_code(): string
{
    if (!is_file(FILE_SETUP)) {
        return '';
    }
    $lines = explode("\n", (string) file_get_contents(FILE_SETUP));
    $data = json_decode($lines[1] ?? '', true);
    return (string) ($data['code'] ?? '');
}

// ---------- Protection contre les essais de mot de passe ----------
function client_key(): string
{
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'inconnu') . '|cdm');
}

function security_state(): array
{
    $data = read_private(FILE_SECURITY) ?? [];
    $now = time();
    // On oublie les entrées anciennes
    foreach ($data as $key => $entry) {
        if (($entry['last'] ?? 0) < $now - 86400) {
            unset($data[$key]);
        }
    }
    return $data;
}

function lock_remaining(): int
{
    $entry = security_state()[client_key()] ?? null;
    return ($entry && ($entry['until'] ?? 0) > time()) ? (int) $entry['until'] - time() : 0;
}

function record_failure(): void
{
    $data = security_state();
    $key = client_key();
    $entry = $data[$key] ?? ['count' => 0, 'until' => 0];
    $entry['count'] = ($entry['until'] ?? 0) > time() ? $entry['count'] : $entry['count'] + 1;
    $entry['last'] = time();
    if ($entry['count'] >= MAX_ATTEMPTS) {
        $entry['until'] = time() + LOCK_MINUTES * 60;
        $entry['count'] = 0;
    }
    $data[$key] = $entry;
    write_private(FILE_SECURITY, $data);
}

function clear_failures(): void
{
    $data = security_state();
    unset($data[client_key()]);
    write_private(FILE_SECURITY, $data);
}

function attempts_left(): int
{
    $entry = security_state()[client_key()] ?? null;
    return MAX_ATTEMPTS - (int) ($entry['count'] ?? 0);
}

// ---------- Tarifs ----------
function load_tarifs(): array
{
    $data = read_json(FILE_TARIFS);
    return ($data && !empty($data['gabarits'])) ? $data : DEFAULT_TARIFS;
}

function text_cut(string $s, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($s, 0, $length) : substr($s, 0, $length);
}
