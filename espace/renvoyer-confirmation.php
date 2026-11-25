<?php
require __DIR__ . '/../app/bootstrap.php';

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(post('email', 190));
    if (throttle_count('resend:ip:' . client_ip(), 3600) < 5 && throttle_count('resend:email:' . $email, 3600) < 3) {
        throttle_hit('resend:ip:' . client_ip());
        throttle_hit('resend:email:' . $email);
        $u = q_one('SELECT * FROM users WHERE email = ? AND email_verified_at IS NULL', [$email]);
        if ($u) {
            $token = create_token((int) $u['id'], 'verify', 48 * 3600);
            send_mail($u['email'], 'Confirmez votre adresse e-mail', greet($u)
                . "Voici votre nouveau lien pour activer votre compte (valable 48 heures) :\n" . url('/espace/confirmer.php?t=' . $token));
        }
    }
    $done = true; // même message dans tous les cas
}

page_start('Renvoyer la confirmation');
?>
<div class="card narrow">
  <h1>Recevoir un nouveau lien</h1>
<?php if ($done): ?>
  <p>Si un compte non confirmé existe pour cette adresse, un nouvel e-mail vient d'être envoyé.</p>
  <p><a href="/espace/connexion.php">Retour à la connexion</a></p>
<?php else: ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Adresse e-mail<input type="email" name="email" required autocomplete="email"></label>
    <button class="btn" type="submit">Envoyer</button>
  </form>
<?php endif; ?>
</div>
<?php page_end();
