<?php
/* Photo d'un chien : visible seulement par son propriétaire et par la gérante. */
require __DIR__ . '/../app/bootstrap.php';
$user = require_login();
$id = query_int('id');
$dog = $user['role'] === 'gerante'
    ? q_one('SELECT * FROM dogs WHERE id = ?', [$id])
    : q_one('SELECT * FROM dogs WHERE id = ? AND user_id = ?', [$id, $user['id']]);
if (!$dog || empty($dog['photo']) || !is_file($path = dog_photo_path($dog['photo']))) {
    http_response_code(404);
    exit('Photo introuvable.');
}
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
