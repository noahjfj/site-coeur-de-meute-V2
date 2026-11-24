<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action', 20);
    $full = q_one('SELECT * FROM users WHERE id = ?', [$uid]);

    if ($action === 'profile') {
        $first = post('first_name', 80);
        $last = post('last_name', 80);
        if ($first === '' || $last === '') {
            $errors[] = 'Indiquez votre prénom et votre nom.';
        } else {
            q('UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?', [$first, $last, post('phone', 40), post('address', 255), $uid]);
            flash('success', 'Vos coordonnées sont enregistrées.');
            redirect('/espace/compte.php');
        }
    } elseif ($action === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $full['password_hash'])) {
            $errors[] = 'Le mot de passe actuel est incorrect.';
        } elseif ($p = password_problem($new, $full['email'])) {
            $errors[] = $p;
        } else {
            $full['password_hash'] = hash_password($new);
            q('UPDATE users SET password_hash = ? WHERE id = ?', [$full['password_hash'], $uid]);
            log_in($full); // garde cette session ouverte, ferme toutes les autres
            audit($uid, 'password_changed');
            send_mail($full['email'], 'Votre mot de passe a été modifié', greet($full) . "Le mot de passe de votre espace client vient d'être changé.\n\nSi ce n'était pas vous, contactez la pension immédiatement.");
            flash('success', 'Votre mot de passe est changé.');
            redirect('/espace/compte.php');
        }
    } elseif ($action === 'delete') {
        if (!password_verify((string) ($_POST['confirm_password'] ?? ''), $full['password_hash'])) {
            $errors[] = 'Mot de passe incorrect : le compte n\'a pas été supprimé.';
        } else {
            delete_user_account($uid);
            end_session();
            session_start();
            flash('info', 'Votre compte et toutes vos données ont été supprimés.');
            redirect('/espace/connexion.php');
        }
    }
}

page_start('Mon compte', 'client', 'compte');
?>
<p class="eyebrow">Mon compte</p>
<h1>Mes informations</h1>
<?php errors_box($errors); ?>
<div class="cols">
  <form method="post" class="card form">
    <h2>Coordonnées</h2>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="profile">
    <div class="grid2">
      <label>Prénom<input type="text" name="first_name" value="<?= h($user['first_name']) ?>" required maxlength="80" autocomplete="given-name"></label>
      <label>Nom<input type="text" name="last_name" value="<?= h($user['last_name']) ?>" required maxlength="80" autocomplete="family-name"></label>
    </div>
    <label>Adresse e-mail<input type="email" value="<?= h($user['email']) ?>" disabled><span class="hint">Pour changer d'adresse, contactez la pension.</span></label>
    <label>Téléphone<input type="tel" name="phone" value="<?= h($user['phone']) ?>" maxlength="40" autocomplete="tel"></label>
    <label>Adresse postale<input type="text" name="address" value="<?= h($user['address']) ?>" maxlength="255" autocomplete="street-address"></label>
    <button class="btn" type="submit">Enregistrer</button>
  </form>

  <form method="post" class="card form">
    <h2>Mot de passe</h2>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="password">
    <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label>
    <label>Nouveau mot de passe<input type="password" name="new_password" required minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password"><span class="hint"><?= PASSWORD_MIN ?> caractères minimum.</span></label>
    <button class="btn" type="submit">Changer le mot de passe</button>
  </form>
</div>

<section class="section">
  <h2>Mes données</h2>
  <p>Vous pouvez supprimer votre compte à tout moment : vos coordonnées, les fiches de vos chiens, vos demandes et vos contrats seront effacés définitivement. <a href="/espace/confidentialite.php">En savoir plus sur vos données</a>.</p>
  <form method="post" class="danger-zone form" data-confirm="Supprimer définitivement votre compte et toutes vos données ?">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <label>Confirmez avec votre mot de passe<input type="password" name="confirm_password" required autocomplete="current-password"></label>
    <button type="submit" class="btn btn--ghost">Supprimer mon compte</button>
  </form>
</section>
<?php page_end(['/js/espace.js']);
