<?php
/*
 * Agenda des séjours confirmés, à ajouter dans Google Agenda (« À partir de l'URL »).
 * Pas de connexion ici : l'accès se fait avec la clé secrète du lien (modifiable dans la page Planning).
 */
require __DIR__ . '/../app/bootstrap.php';
$key = is_string($_GET['cle'] ?? null) ? $_GET['cle'] : '';
if (!preg_match('/^[a-f0-9]{48}$/', $key) || !hash_equals(agenda_token(), $key)) {
    http_response_code(404);
    exit('Agenda introuvable.');
}
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="coeur-de-meute.ics"');
header('Cache-Control: private, max-age=900');
header('X-Robots-Tag: noindex');
echo planning_ics();
