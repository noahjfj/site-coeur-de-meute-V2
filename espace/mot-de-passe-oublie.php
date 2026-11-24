<?php
require __DIR__ . '/../app/bootstrap.php';

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(post('email', 190));
    if (throttle_count('forgot:ip:' . client_ip(), 3600) < 5 && throttle_count('forgot:email:' . $email, 3600) < 3) {
        throttle_hit('forgot:ip:' . client_ip());
        throttle_hit('forgot:email:' . $email);
        $u = q_one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($u) {
            $token = create_token((int) $u['id'], 'reset', 3600);
            audit((int) $u['id'], 'password_reset_requested');
            send_mail($u['email'], 'Choisir un nouveau mot de passe', greet($u)
                . "Pour choisir un nouveau mot de passe, cliquez sur ce lien (valable 1 heure, une seule fois) :\n"
                . url('/espace/reinitialiser.php?t=' . $token)
                . "\n\nSi vous n'avez rien demandé, ignorez cet e-mail : votre mot de passe actuel reste valable.");
        }
    }
    $done = true; // même réponse que l'adresse existe ou non
}

page_start('Mot de passe oublié');
?>
<div class="card narrow">
  <h1>Mot de passe oublié</h1>
<?php if ($done): ?>
  <p>Si un compte existe pour cette adresse, vous allez recevoir un e-mail avec un lien valable 1 heure.</p>
  <p><a href="/espace/connexion.php">Retour à la connexion</a></p>
<?php else: ?>
  <p>Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Adresse e-mail<input type="email" name="email" required autocomplete="email"></label>
    <button class="btn" type="submit">Recevoir le lien</button>
  </form>
<?php endif; ?>
</div>
<?php page_end();
