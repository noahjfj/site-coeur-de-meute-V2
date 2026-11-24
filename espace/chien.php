<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];

$id = query_int('id') ?: post_int('id');
$dog = null;
if ($id) {
    $dog = find_own_dog($uid, $id);
    if (!$dog) {
        http_response_code(404);
        exit('Chien introuvable.');
    }
}

$errors = [];
$v = $dog ?? ['name' => '', 'breed' => '', 'birth_date' => null, 'sex' => '', 'size' => '', 'sterilized' => 0, 'vaccines_until' => null, 'vet' => '', 'health' => '', 'temperament' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'delete' && $dog) {
        $active = q_one("SELECT id FROM stay_requests WHERE dog_id = ? AND status IN ('pending','confirmed') AND end_date >= ?", [$dog['id'], today()]);
        if ($active) {
            $errors[] = $dog['name'] . ' a une demande en cours : annulez-la d\'abord ou contactez la pension.';
        } else {
            q('DELETE FROM dogs WHERE id = ? AND user_id = ?', [$dog['id'], $uid]);
            audit($uid, 'dog_deleted', (string) $dog['id']);
            flash('success', 'La fiche de ' . $dog['name'] . ' a été supprimée.');
            redirect('/espace/');
        }
    } else {
        [$v, $errors] = dog_from_post();
        if (!$errors) {
            $vals = [$v['name'], $v['breed'], $v['birth_date'], $v['sex'], $v['size'], $v['sterilized'], $v['vaccines_until'], $v['vet'], $v['health'], $v['temperament']];
            if ($dog) {
                q('UPDATE dogs SET name = ?, breed = ?, birth_date = ?, sex = ?, size = ?, sterilized = ?, vaccines_until = ?, vet = ?, health = ?, temperament = ? WHERE id = ? AND user_id = ?', [...$vals, $dog['id'], $uid]);
            } else {
                if (count(user_dogs($uid)) >= 10) {
                    $errors[] = 'Maximum 10 chiens par compte. Contactez la pension si besoin.';
                } else {
                    q('INSERT INTO dogs (name, breed, birth_date, sex, size, sterilized, vaccines_until, vet, health, temperament, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$vals, $uid]);
                }
            }
            if (!$errors) {
                flash('success', 'La fiche de ' . $v['name'] . ' est enregistrée.');
                redirect('/espace/');
            }
        }
    }
}

page_start($dog ? 'Fiche de ' . $dog['name'] : 'Ajouter un chien', 'client', 'accueil');
?>
<p class="eyebrow"><a href="/espace/">Mon espace</a></p>
<h1><?= $dog ? 'Fiche de ' . h($dog['name']) : 'Ajouter un chien' ?></h1>
<?php errors_box($errors); ?>
<form method="post" class="card form form--wide">
  <?= csrf_field() ?>
  <?php if ($dog): ?><input type="hidden" name="id" value="<?= (int) $dog['id'] ?>"><?php endif; ?>
  <div class="grid2">
    <label>Nom<input type="text" name="name" value="<?= h($v['name']) ?>" required maxlength="80"></label>
    <label>Race<input type="text" name="breed" value="<?= h($v['breed']) ?>" maxlength="120"></label>
    <label>Date de naissance<input type="date" name="birth_date" value="<?= h($v['birth_date']) ?>" max="<?= today() ?>"></label>
    <label>Sexe
      <select name="sex">
        <option value=""<?= selected($v['sex'] === '') ?>>—</option>
        <option value="male"<?= selected($v['sex'] === 'male') ?>>Mâle</option>
        <option value="femelle"<?= selected($v['sex'] === 'femelle') ?>>Femelle</option>
      </select></label>
  </div>
  <fieldset class="choices">
    <legend>Gabarit (pour le tarif)</legend>
    <?php foreach (load_tarifs()['gabarits'] as $g): ?>
      <label class="choice"><input type="radio" name="size" value="<?= h($g['id']) ?>"<?= checked($v['size'] === $g['id']) ?> required>
        <span><strong><?= h($g['nom']) ?></strong><small><?= h($g['description']) ?></small></span></label>
    <?php endforeach; ?>
  </fieldset>
  <label class="check"><input type="checkbox" name="sterilized" value="1"<?= checked((int) $v['sterilized'] === 1) ?>><span>Stérilisé(e)</span></label>
  <div class="grid2">
    <label>Vaccins valables jusqu'au<input type="date" name="vaccines_until" value="<?= h($v['vaccines_until']) ?>"></label>
    <label>Vétérinaire (nom, commune, téléphone)<input type="text" name="vet" value="<?= h($v['vet']) ?>" maxlength="190"></label>
  </div>
  <label>Santé : allergies, traitements, régime<textarea name="health" rows="3" maxlength="2000"><?= h($v['health']) ?></textarea></label>
  <label>Caractère et habitudes<textarea name="temperament" rows="3" maxlength="2000"><?= h($v['temperament']) ?></textarea></label>
  <div class="actions">
    <button type="submit" class="btn">Enregistrer</button>
    <a href="/espace/">Annuler</a>
  </div>
</form>
<?php if ($dog): ?>
<form method="post" class="danger-zone" data-confirm="Supprimer définitivement la fiche de <?= h($dog['name']) ?> ?">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $dog['id'] ?>">
  <input type="hidden" name="action" value="delete">
  <button type="submit" class="btn btn--ghost">Supprimer cette fiche</button>
</form>
<?php endif; ?>
<?php page_end(['/js/espace.js']);
