<?php
/*
 * Prix publics lus par le simulateur du site (aucune donnée personnelle).
 */
declare(strict_types=1);
define('CDM', true);
const APP_DIR = __DIR__ . '/../app';
define('SITE_DIR', dirname(__DIR__));

function config(string $key, $default = null)
{
    static $cfg = null;
    $cfg ??= is_file(APP_DIR . '/config.php') ? require APP_DIR . '/config.php' : [];
    return array_key_exists($key, $cfg) ? $cfg[$key] : $default;
}

require APP_DIR . '/lib/tarifs.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo json_encode(load_tarifs(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
