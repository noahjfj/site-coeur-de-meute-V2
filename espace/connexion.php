<?php
require __DIR__ . '/../app/bootstrap.php';

if ($u = current_user()) {
    redirect($u['role'] === 'gerante' ? '/gerante/' : '/espace/');
}

$error = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(post('email', 190));
    [$user, $error] = attempt_login($email, (string) ($_POST['password'] ?? ''));
    if ($user) {
        $after = $_SESSION['after_login'] ?? '';
        log_in($user);
        $home = $user['role'] === 'gerante' ? '/gerante/' : '/espace/';
        // Retour à la page demandée seulement si c'est une page de son espace.
        $prefix = $user['role'] === 'gerante' ? '/gerante/' : '/espace/';
        redirect(is_string($after) && str_starts_with($after, $prefix) ? $after : $home);
    }
}

page_start('Connexion');
?>
<div class="auth">
  <div class="auth__intro">
    <span class="auth__blob auth__blob--1" aria-hidden="true"></span>
    <span class="auth__blob auth__blob--2" aria-hidden="true"></span>
    <img class="auth__logo" src="/assets/img/logo-coeur-de-meute.svg" alt="" width="96" height="84">
    <p class="eyebrow">Espace client</p>
    <h1 class="welcome">Bienvenue dans la meute</h1>
    <p class="lead">Retrouvez les fiches de vos chiens, demandez vos dates de séjour, suivez vos cartes journée et vos contrats.</p>
    <ul class="auth__perks">
      <li><span aria-hidden="true">🐾</span>Les fiches de vos chiens, toujours à jour</li>
      <li><span aria-hidden="true">📅</span>Vos demandes de séjour, confirmées par e-mail</li>
      <li><span aria-hidden="true">🎟️</span>Vos cartes journée et vos contrats au même endroit</li>
    </ul>
  </div>
  <div class="card auth__box">
    <div class="tabs"><a href="/espace/connexion.php" aria-current="page">Se connecter</a><a href="/espace/inscription.php">Créer un compte</a></div>
    <?php if ($error === 'unverified'): ?>
      <p class="flash flash--error" role="alert">Votre adresse e-mail n'est pas encore confirmée. Cliquez sur le lien reçu par e-mail (pensez aux indésirables). <a href="/espace/renvoyer-confirmation.php">Renvoyer l'e-mail</a></p>
    <?php elseif ($error): ?>
      <p class="flash flash--error" role="alert"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Adresse e-mail<input type="email" name="email" value="<?= h($email) ?>" required autocomplete="email" autofocus></label>
      <label>Mot de passe<input type="password" name="password" required autocomplete="current-password"></label>
      <a class="form__aside" href="/espace/mot-de-passe-oublie.php">Mot de passe oublié ?</a>
      <button type="submit" class="btn">Se connecter</button>
    </form>
  </div>
</div>
<?php page_end();
