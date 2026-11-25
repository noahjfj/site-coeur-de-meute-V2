<?php
/* La gérante coche ou décoche un paiement (acompte ou totalité), puis revient sur sa page. */
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gerante/');
}
[$what, $value] = array_pad(explode(':', post('set', 12), 2), 2, '');
$ok = in_array($value, ['0', '1'], true) && set_payment((int) $me['id'], post_int('id'), $what, $value === '1');
flash($ok ? 'success' : 'error', $ok ? ($value === '1' ? 'Paiement noté.' : 'Paiement retiré.') : 'Impossible de modifier ce paiement.');
redirect(safe_gerante_back(post('back', 300)));
