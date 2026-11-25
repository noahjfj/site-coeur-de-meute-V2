<?php
if (!defined('CDM')) { http_response_code(404); exit; }

const LOGIN_MAX_PER_EMAIL = 5;    // essais ratés par adresse…
const LOGIN_MAX_PER_IP    = 20;   // … et par connexion internet
const LOGIN_WINDOW        = 900;  // sur 15 minutes

// Empreinte factice : vérifiée quand l'adresse n'existe pas, pour que la réponse prenne le même temps.
const DUMMY_HASH = '$2y$10$OcplZpdNm9p/OGMYCsYqvuL.CTDCP0A8TrFokbPXvXLzbwzw2cNDG';

/** Utilisateur connecté (relu dans la base à chaque page), ou null. */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    $uid = $_SESSION['uid'] ?? null;
    if (!is_int($uid)) {
        return null;
    }
    $u = q_one('SELECT id, email, first_name, last_name, phone, address, role, password_hash, email_verified_at FROM users WHERE id = ?', [$uid]);
    // Si le mot de passe a changé ailleurs, cette session n'est plus valable.
    if (!$u || !hash_equals((string) ($_SESSION['pwfp'] ?? ''), password_fingerprint($u['password_hash']))) {
        end_session();
        session_start();
        return null;
    }
    unset($u['password_hash']);
    return $cache = $u;
}

function password_fingerprint(string $hash): string
{
    return hash('sha256', 'cdm-session|' . $hash);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/espace/';
        redirect('/espace/connexion.php');
    }
    return $u;
}

function require_client(): array
{
    $u = require_login();
    if ($u['role'] === 'gerante') {
        redirect('/gerante/');
    }
    return $u;
}

function require_gerante(): array
{
    $u = require_login();
    if ($u['role'] !== 'gerante') {
        http_response_code(403);
        exit('Accès réservé à la gérante.');
    }
    return $u;
}

function log_in(array $user): void
{
    session_regenerate_id(true); // nouvelle clé de session : empêche le vol de session « fixée » à l'avance
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['pwfp'] = password_fingerprint($user['password_hash']);
    $_SESSION['started'] = time();
    $_SESSION['seen'] = time();
    unset($_SESSION['csrf']);
}

/**
 * Vérifie e-mail + mot de passe. Retourne [utilisateur|null, message d'erreur|null].
 * Le message ne dit jamais si c'est l'adresse ou le mot de passe qui est faux.
 */
function attempt_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    $bEmail = 'login:email:' . $email;
    $bIp = 'login:ip:' . client_ip();

    if (throttle_count($bEmail, LOGIN_WINDOW) >= LOGIN_MAX_PER_EMAIL || throttle_count($bIp, LOGIN_WINDOW) >= LOGIN_MAX_PER_IP) {
        audit(null, 'login_blocked', $email);
        return [null, 'Trop de tentatives. Pour votre sécurité, réessayez dans 15 minutes.'];
    }

    $user = q_one('SELECT * FROM users WHERE email = ?', [$email]);
    $ok = password_verify($password, $user['password_hash'] ?? DUMMY_HASH);

    if (!$user || !$ok) {
        throttle_hit($bEmail);
        throttle_hit($bIp);
        audit($user ? (int) $user['id'] : null, 'login_failed', $email);
        return [null, 'Adresse e-mail ou mot de passe incorrect.'];
    }
    if ($user['email_verified_at'] === null) {
        return [null, 'unverified'];
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $user['password_hash'] = hash_password($password);
        q('UPDATE users SET password_hash = ? WHERE id = ?', [$user['password_hash'], $user['id']]);
    }
    throttle_clear($bEmail);
    audit((int) $user['id'], 'login', '');
    return [$user, null];
}
