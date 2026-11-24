<?php
/*
 * Espace administrateur — Coeur de Meute
 *
 *  - Tarifs de la pension (data/tarifs.json)
 *  - Photos du site et galerie (data/photos.json + assets/uploads/)
 *  - Mon compte (mot de passe)
 *
 * Première visite : un code d'installation est demandé. Il se trouve dans le fichier
 * admin/code-installation.php, à ouvrir avec le gestionnaire de fichiers de l'hébergeur.
 * Mot de passe oublié : supprimez admin/acces.php sur l'hébergement, puis retournez sur /admin/.
 */

declare(strict_types=1);

define('CDM_ADMIN', true);
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/photos.php';
require __DIR__ . '/inc/actions.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_name('cdm_admin');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post();
}

$access = load_access();
$pages  = ['accueil' => 'Tableau de bord', 'tarifs' => 'Tarifs', 'photos' => 'Photos', 'compte' => 'Mon compte'];

if ($access === null) {
    $view = 'setup';
    $setupCode = setup_code(); // crée le fichier code-installation.php s'il n'existe pas
} elseif (!is_logged_in()) {
    $view = 'login';
} else {
    $view = isset($pages[$_GET['page'] ?? '']) ? $_GET['page'] : 'accueil';
}

$flashes = take_flashes();
$oldUser = $_SESSION['old_user'] ?? '';
require __DIR__ . '/views/layout.php';
