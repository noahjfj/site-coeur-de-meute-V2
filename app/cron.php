<?php
/*
 * Tâche automatique quotidienne (à programmer chez l'hébergeur, rubrique « Tâches Cron ») :
 *   php /chemin/vers/le/site/app/cron.php
 * - rappelle aux clients que leur carte journée expire dans un mois ;
 * - fait le ménage dans les données techniques périmées.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/bootstrap.php';

$soon = (new DateTimeImmutable('today'))->modify('+1 month')->format('Y-m-d');
$cards = q_all('SELECT c.*, u.email, u.first_name FROM cards c JOIN users u ON u.id = c.user_id
    WHERE c.reminder_sent = 0 AND c.expires_at BETWEEN CURDATE() AND ? AND c.used_days < c.paid_days + c.free_days', [$soon]);
foreach ($cards as $c) {
    $left = card_remaining($c);
    send_mail($c['email'], 'Votre carte journée expire bientôt', greet($c)
        . 'Il vous reste ' . $left . ' journée' . ($left > 1 ? 's' : '') . ' sur votre carte, valable jusqu\'au ' . date_fr($c['expires_at']) . ".\n\n"
        . 'Pensez à les utiliser : ' . url('/espace/demande.php'));
    q('UPDATE cards SET reminder_sent = 1 WHERE id = ?', [$c['id']]);
}

q('DELETE FROM throttle WHERE created_at < (NOW() - INTERVAL 1 DAY)');
q('DELETE FROM tokens WHERE expires_at < (NOW() - INTERVAL 7 DAY)');
q('DELETE FROM audit_log WHERE created_at < (NOW() - INTERVAL 1 YEAR)');
echo count($cards) . " rappel(s) envoyé(s)\n";
