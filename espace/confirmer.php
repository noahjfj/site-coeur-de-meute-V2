<?php
require __DIR__ . '/../app/bootstrap.php';

$token = is_string($_GET['t'] ?? null) ? $_GET['t'] : '';
$t = find_token($token, 'verify');

if ($t && consume_token((int) $t['id'])) {
    q('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [$t['user_id']]);
    audit((int) $t['user_id'], 'email_verified');
    flash('success', 'Votre adresse est confirmée. Vous pouvez vous connecter.');
    redirect('/espace/connexion.php');
}

page_start('Lien expiré');
?>
<div class="card narrow">
  <h1>Ce lien n'est plus valable</h1>
  <p>Il a peut-être déjà été utilisé ou il a expiré (48 heures).</p>
  <p><a class="btn" href="/espace/renvoyer-confirmation.php">Recevoir un nouveau lien</a></p>
</div>
<?php page_end();
