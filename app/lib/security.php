<?php
if (!defined('CDM')) { http_response_code(404); exit; }

const SESSION_IDLE_SECONDS     = 2 * 3600;   // déconnexion après 2 h sans activité
const SESSION_ABSOLUTE_SECONDS = 12 * 3600;  // et au plus tard après 12 h

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function send_security_headers(): void
{
    // Le navigateur n'exécute que les scripts et styles du site lui-même.
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, private');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function start_secure_session(): void
{
    session_name(is_https() ? '__Host-cdm' : 'cdm');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    $now = time();
    if (isset($_SESSION['uid'])) {
        $tooOld  = ($now - ($_SESSION['started'] ?? 0)) > SESSION_ABSOLUTE_SECONDS;
        $idle    = ($now - ($_SESSION['seen'] ?? 0)) > SESSION_IDLE_SECONDS;
        if ($tooOld || $idle) {
            end_session();
            session_start();
            flash('info', 'Votre session a expiré, merci de vous reconnecter.');
        }
    }
    $_SESSION['seen'] = $now;
}

function end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite']]);
    }
    session_destroy();
}

/* ---------- Jeton anti-falsification (CSRF) ---------- */

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

/**
 * Tout formulaire envoyé doit venir du site lui-même et porter le jeton secret de la session.
 * Sinon il est refusé : un site piège ne peut pas agir à la place du client.
 */
function check_post_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && $origin !== 'null') {
        $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $self = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === '' || $host !== $self) {
            http_response_code(403);
            exit('Requête refusée.');
        }
    }
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Le formulaire a expiré. Revenez en arrière, rechargez la page et réessayez.');
    }
}

/* ---------- Limitation des essais (anti-robots) ---------- */

function throttle_key(string $bucket): string
{
    return hash('sha256', $bucket);
}

function throttle_hit(string $bucket): void
{
    q('INSERT INTO throttle (bucket) VALUES (?)', [throttle_key($bucket)]);
    if (random_int(1, 50) === 1) {
        q('DELETE FROM throttle WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function throttle_count(string $bucket, int $seconds): int
{
    return (int) q('SELECT COUNT(*) FROM throttle WHERE bucket = ? AND created_at > (NOW() - INTERVAL ? SECOND)', [throttle_key($bucket), $seconds])->fetchColumn();
}

function throttle_clear(string $bucket): void
{
    q('DELETE FROM throttle WHERE bucket = ?', [throttle_key($bucket)]);
}

/* ---------- Jetons envoyés par e-mail ---------- */

/** Crée un lien à usage unique ; seule son empreinte est stockée, jamais le lien lui-même. */
function create_token(int $userId, string $type, int $ttlSeconds): string
{
    q('UPDATE tokens SET used_at = NOW() WHERE user_id = ? AND type = ? AND used_at IS NULL', [$userId, $type]);
    $token = bin2hex(random_bytes(32));
    q('INSERT INTO tokens (user_id, type, token_hash, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ? SECOND)', [$userId, $type, hash('sha256', $token), $ttlSeconds]);
    return $token;
}

/** Retourne l'utilisateur du jeton s'il est valide, sans le consommer. */
function find_token(string $token, string $type): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return q_one('SELECT id, user_id FROM tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token), $type]);
}

function consume_token(int $tokenId): bool
{
    return q('UPDATE tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL', [$tokenId])->rowCount() === 1;
}

/* ---------- Mots de passe ---------- */

const PASSWORD_MIN = 10;

function password_problem(string $password, string $email = ''): ?string
{
    if (mb_strlen($password) < PASSWORD_MIN) {
        return 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN . ' caractères.';
    }
    if (strlen($password) > 200) {
        return 'Le mot de passe est trop long.';
    }
    $lower = mb_strtolower($password);
    $common = ['motdepasse', 'password', '1234567890', 'azertyuiop', 'qwertyuiop', 'coeurdemeute', '0123456789', 'aaaaaaaaaa'];
    foreach ($common as $c) {
        if (str_contains($lower, $c)) {
            return 'Ce mot de passe est trop facile à deviner.';
        }
    }
    if ($email !== '' && str_contains($lower, mb_strtolower(strstr($email, '@', true) ?: '#'))) {
        return 'Le mot de passe ne doit pas contenir votre adresse e-mail.';
    }
    if (count(array_unique(mb_str_split($password))) < 5) {
        return 'Ce mot de passe est trop facile à deviner.';
    }
    return null;
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}
