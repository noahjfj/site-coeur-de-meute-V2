<?php
require __DIR__ . '/../app/bootstrap.php';

$token = is_string($_REQUEST['t'] ?? null) ? $_REQUEST['t'] : '';
$t = find_token($token, 'reset');
$errors = [];

if ($t && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $user = q_one('SELECT * FROM users WHERE id = ?', [$t['user_id']]);
    if ($p = password_problem($password, $user['email'])) {
        $errors[] = $p;
    } elseif ($password !== (string) ($_POST['password2'] ?? '')) {
        $errors[] = 'Les deux mots de passe ne sont pas identiques.';
    } elseif (consume_token((int) $t['id'])) {
        // Nouveau mot de passe : toutes les autres sessions ouvertes sont automatiquement fermées.
        q('UPDATE users SET password_hash = ?, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [hash_password($password), $user['id']]);
        throttle_clear('login:email:' . $user['email']);
        audit((int) $user['id'], 'password_reset_done');
        send_mail($user['email'], 'Votre mot de passe a été modifié', greet($user)
            . "Le mot de passe de votre espace client vient d'être changé.\n\nSi ce n'était pas vous, contactez la pension immédiatement.");
        flash('success', 'Votre mot de passe est changé. Vous pouvez vous connecter.');
        redirect('/espace/connexion.php');
    } else {
        $t = null;
    }
}

page_start('Nouveau mot de passe');
?>
<div class="card narrow">
<?php if (!$t): ?>
  <h1>Ce lien n'est plus valable</h1>
  <p>Il a déjà été utilisé ou il a expiré (1 heure).</p>
  <p><a class="btn" href="/espace/mot-de-passe-oublie.php">Recevoir un nouveau lien</a></p>
<?php else: ?>
  <h1>Choisir un nouveau mot de passe</h1>
  <?php errors_box($errors); ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="t" value="<?= h($token) ?>">
    <label>Nouveau mot de passe<input type="password" name="password" required minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password">
      <span class="hint"><?= PASSWORD_MIN ?> caractères minimum.</span></label>
    <label>Répétez le mot de passe<input type="password" name="password2" required autocomplete="new-password"></label>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
<?php endif; ?>
</div>
<?php page_end();
