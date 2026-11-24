<?php
/*
 * Création du compte de la gérante (une seule fois).
 * Protégé par le code secret « setup_token » de app/config.php, et bloqué dès qu'un compte gérante existe.
 */
require __DIR__ . '/../app/bootstrap.php';

$setup = (string) config('setup_token', '');
$exists = (bool) q_one("SELECT id FROM users WHERE role = 'gerante' LIMIT 1");
$errors = [];

if ($exists || strlen($setup) < 32) {
    http_response_code(404);
    exit('Page indisponible.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (throttle_count('setup:ip:' . client_ip(), 3600) >= 5) {
        http_response_code(429);
        exit('Trop d\'essais. Réessayez dans une heure.');
    }
    if (!hash_equals($setup, (string) ($_POST['setup_token'] ?? ''))) {
        throttle_hit('setup:ip:' . client_ip());
        $errors[] = 'Code secret incorrect.';
    }
    $email = mb_strtolower(post('email', 190));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }
    if ($p = password_problem($password, $email)) {
        $errors[] = $p;
    }
    if (strlen($password) < 14) {
        $errors[] = 'Pour le compte gérante, utilisez au moins 14 caractères.';
    }
    if (q_one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors[] = 'Cette adresse est déjà utilisée par un compte client.';
    }
    if (!$errors) {
        q("INSERT INTO users (email, password_hash, first_name, last_name, role, email_verified_at) VALUES (?, ?, ?, ?, 'gerante', NOW())",
            [$email, hash_password($password), post('first_name', 80) ?: 'Gérante', post('last_name', 80) ?: 'Coeur de Meute']);
        audit((int) db()->lastInsertId(), 'manager_created');
        flash('success', 'Compte gérante créé. Pensez à vider « setup_token » dans app/config.php. Vous pouvez vous connecter.');
        redirect('/espace/connexion.php');
    }
}

page_start('Créer le compte gérante');
?>
<div class="card narrow">
  <h1>Créer le compte gérante</h1>
  <p class="muted">Cette page ne fonctionne qu'une seule fois.</p>
  <?php errors_box($errors); ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Code secret (setup_token du fichier config.php)<input type="password" name="setup_token" required autocomplete="off"></label>
    <div class="grid2">
      <label>Prénom<input type="text" name="first_name" maxlength="80"></label>
      <label>Nom<input type="text" name="last_name" maxlength="80"></label>
    </div>
    <label>Adresse e-mail<input type="email" name="email" required autocomplete="email"></label>
    <label>Mot de passe (14 caractères minimum)<input type="password" name="password" required minlength="14" autocomplete="new-password"></label>
    <button class="btn" type="submit">Créer le compte</button>
  </form>
</div>
<?php page_end();
