<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_login();

$id = query_int('id');
// La gérante peut ouvrir tous les contrats ; un client uniquement les siens.
$c = $user['role'] === 'gerante'
    ? q_one('SELECT * FROM contracts WHERE id = ?', [$id])
    : q_one('SELECT * FROM contracts WHERE id = ? AND user_id = ?', [$id, $user['id']]);

if (!$c) {
    http_response_code(404);
    exit('Document introuvable.');
}
audit((int) $user['id'], 'contract_downloaded', (string) $c['id']);
send_contract_file($c);
