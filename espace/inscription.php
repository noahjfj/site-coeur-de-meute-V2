<?php
require __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('/espace/');
}

$errors = [];
$sent = false;
$v = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = [
        'first_name' => post('first_name', 80),
        'last_name'  => post('last_name', 80),
        'email'      => mb_strtolower(post('email', 190)),
        'phone'      => post('phone', 40),
    ];
    $password = (string) ($_POST['password'] ?? '');

    if ($v['first_name'] === '' || $v['last_name'] === '') {
        $errors[] = 'Indiquez votre prénom et votre nom.';
    }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse e-mail n\'est pas valide.';
    }
    if ($p = password_problem($password, $v['email'])) {
        $errors[] = $p;
    }
    if (($_POST['consent'] ?? '') !== '1') {
        $errors[] = 'Merci d\'accepter la politique de confidentialité.';
    }
    if (throttle_count('signup:ip:' . client_ip(), 3600) >= 5) {
        $errors = ['Trop de demandes depuis cette connexion. Réessayez dans une heure.'];
    }

    if (!$errors) {
        throttle_hit('signup:ip:' . client_ip());
        $existing = q_one('SELECT * FROM users WHERE email = ?', [$v['email']]);
        if ($existing) {
            // On ne révèle pas à l'écran qu'un compte existe : on prévient la personne par e-mail.
            send_mail($existing['email'], 'Vous avez déjà un compte', greet($existing)
                . "Quelqu'un (sans doute vous) a essayé de créer un compte avec cette adresse, mais vous en avez déjà un.\n\n"
                . 'Pour vous connecter : ' . url('/espace/connexion.php') . "\n"
                . 'Mot de passe oublié : ' . url('/espace/mot-de-passe-oublie.php') . "\n\n"
                . "Si ce n'était pas vous, ignorez simplement cet e-mail.");
        } else {
            q('INSERT INTO users (email, password_hash, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)', [$v['email'], hash_password($password), $v['first_name'], $v['last_name'], $v['phone']]);
            $uid = (int) db()->lastInsertId();
            audit($uid, 'signup');
            $token = create_token($uid, 'verify', 48 * 3600);
            send_mail($v['email'], 'Confirmez votre adresse e-mail', greet($v)
                . "Bienvenue dans l'espace client de Coeur de Meute !\n\nPour activer votre compte, cliquez sur ce lien (valable 48 heures) :\n"
                . url('/espace/confirmer.php?t=' . $token) . "\n\nSi vous n'avez pas créé de compte, ignorez cet e-mail.");
        }
        $sent = true;
    }
}

page_start('Créer un compte');
?>
<div class="auth">
  <div class="auth__intro">
    <span class="auth__blob auth__blob--1" aria-hidden="true"></span>
    <span class="auth__blob auth__blob--2" aria-hidden="true"></span>
    <img class="auth__logo" src="/assets/img/logo-coeur-de-meute.svg" alt="" width="96" height="84">
    <p class="eyebrow">Espace client</p>
    <h1 class="welcome">Rejoindre la meute</h1>
    <p class="lead">Créez votre compte pour enregistrer vos chiens, demander vos dates et retrouver vos contrats.</p>
    <ul class="auth__perks">
      <li><span aria-hidden="true">🐾</span>Les fiches de vos chiens, toujours à jour</li>
      <li><span aria-hidden="true">📅</span>Vos demandes de séjour, confirmées par e-mail</li>
      <li><span aria-hidden="true">🎟️</span>Vos cartes journée et vos contrats au même endroit</li>
    </ul>
  </div>
  <div class="card auth__box">
<?php if ($sent): ?>
    <h2>Vérifiez vos e-mails</h2>
    <p>Si l'adresse <strong><?= h($v['email']) ?></strong> est valide, vous allez recevoir un e-mail pour activer votre compte. Pensez à regarder dans les indésirables.</p>
    <p><a href="/espace/connexion.php">Aller à la connexion</a></p>
<?php else: ?>
    <div class="tabs"><a href="/espace/connexion.php">Se connecter</a><a href="/espace/inscription.php" aria-current="page">Créer un compte</a></div>
    <?php errors_box($errors); ?>
    <form method="post" class="form" novalidate>
      <?= csrf_field() ?>
      <div class="grid2">
        <label>Prénom<input type="text" name="first_name" value="<?= h($v['first_name']) ?>" required autocomplete="given-name" maxlength="80"></label>
        <label>Nom<input type="text" name="last_name" value="<?= h($v['last_name']) ?>" required autocomplete="family-name" maxlength="80"></label>
      </div>
      <label>Adresse e-mail<input type="email" name="email" value="<?= h($v['email']) ?>" required autocomplete="email" maxlength="190"></label>
      <label>Téléphone<input type="tel" name="phone" value="<?= h($v['phone']) ?>" autocomplete="tel" maxlength="40"></label>
      <label>Mot de passe<input type="password" name="password" required autocomplete="new-password" minlength="<?= PASSWORD_MIN ?>" maxlength="200">
        <span class="hint"><?= PASSWORD_MIN ?> caractères minimum. Une phrase facile à retenir est idéale.</span></label>
      <label class="check"><input type="checkbox" name="consent" value="1" required>
        <span>J'accepte que mes données soient utilisées pour la gestion des séjours, selon la <a href="/espace/confidentialite.php" target="_blank">politique de confidentialité</a>.</span></label>
      <button type="submit" class="btn">Créer mon compte</button>
    </form>
<?php endif; ?>
  </div>
</div>
<?php page_end();
