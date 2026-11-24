<?php
/*
 * Démarrage commun de toutes les pages de l'espace client et de l'espace gérante.
 * Chaque page fait :  require __DIR__ . '/../app/bootstrap.php';
 */
declare(strict_types=1);

if (!defined('CDM')) {
    define('CDM', true);
}

// Le dossier app/ n'est jamais ouvert directement depuis le web.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

const APP_DIR = __DIR__;
define('SITE_DIR', dirname(__DIR__));

$configFile = APP_DIR . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "L'espace client n'est pas encore configuré (fichier app/config.php manquant).";
    exit;
}
$GLOBALS['CDM_CONFIG'] = require $configFile;

ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Brussels');
mb_internal_encoding('UTF-8');

require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/security.php';
require APP_DIR . '/lib/auth.php';
require APP_DIR . '/lib/mail.php';
require APP_DIR . '/lib/tarifs.php';
require APP_DIR . '/lib/domain.php';
require APP_DIR . '/lib/layout.php';

function config(string $key, $default = null)
{
    $cfg = $GLOBALS['CDM_CONFIG'] ?? [];
    return array_key_exists($key, $cfg) ? $cfg[$key] : $default;
}

if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $e): void {
        error_log('[coeurdemeute] ' . $e);
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (config('debug')) {
            echo '<pre>' . h((string) $e) . '</pre>';
            return;
        }
        echo '<!doctype html><meta charset="utf-8"><title>Erreur</title>'
            . '<p style="font-family:sans-serif;padding:2rem">Une erreur est survenue. Réessayez dans un instant ; si le problème continue, contactez la pension.</p>';
    });

    send_security_headers();
    start_secure_session();
    check_post_request();
}
