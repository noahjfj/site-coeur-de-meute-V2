<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$t = load_tarifs();
$errors = [];

/** Prix saisi : nombre entre 0 et 1000, ou vide (= pas encore fixé) si $nullable. */
function read_price(string $name, bool $nullable, array &$errors, string $label)
{
    $raw = str_replace(',', '.', post($name, 12));
    if ($raw === '' && $nullable) {
        return null;
    }
    if (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > 1000) {
        $errors[] = 'Prix invalide : ' . $label . '.';
        return null;
    }
    $n = round((float) $raw, 2);
    return floor($n) == $n ? (int) $n : $n;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seuil = post_int('seuilJours');
    if ($seuil < 1 || $seuil > 60) {
        $errors[] = 'Le nombre de jours pour le tarif réduit doit être entre 1 et 60.';
    } else {
        $t['seuilJours'] = $seuil;
    }
    foreach ($t['gabarits'] as $i => $g) {
        $id = $g['id'];
        $t['gabarits'][$i]['prix'] = read_price("prix_$id", false, $errors, $g['nom'] . ' séjour');
        $t['gabarits'][$i]['prixLongSejour'] = read_price("long_$id", false, $errors, $g['nom'] . ' long séjour');
        $t['gabarits'][$i]['prixJournee'] = read_price("journee_$id", true, $errors, $g['nom'] . ' journée');
        $t['gabarits'][$i]['prixCarte10'] = read_price("carte10_$id", true, $errors, $g['nom'] . ' carte 10');
        $t['gabarits'][$i]['prixCarte20'] = read_price("carte20_$id", true, $errors, $g['nom'] . ' carte 20');
    }
    foreach (['offertes10' => [0, 10], 'offertes20' => [0, 20], 'validiteMois' => [1, 36]] as $k => [$min, $max]) {
        $raw = post($k, 3);
        if (!ctype_digit($raw) || (int) $raw < $min || (int) $raw > $max) {
            $errors[] = 'Valeur invalide pour les cartes.';
        } else {
            $t['cartes'][$k] = (int) $raw;
        }
    }
    if (!$errors) {
        if (save_tarifs($t)) {
            audit((int) $me['id'], 'tarifs_updated');
            flash('success', 'Les tarifs sont enregistrés. Le site et le simulateur sont à jour.');
            redirect('/gerante/tarifs.php');
        }
        $errors[] = 'Impossible d\'enregistrer : le dossier app/storage n\'est pas modifiable sur l\'hébergement.';
    }
}

page_start('Tarifs', 'gerante', 'tarifs');
?>
<form method="post" class="card section form form--wide">
  <h1>Tarifs</h1>
  <p class="muted">Modifiez un prix et enregistrez : le site et le simulateur se mettent à jour. Les cartes déjà vendues gardent leur prix d'achat. Un champ vide = prix pas encore fixé.</p>
  <?php errors_box($errors); ?>
  <?= csrf_field() ?>
  <div class="table-box">
    <table class="table table--inputs">
      <thead><tr><th scope="col">Gabarit</th><th scope="col">Séjour / jour</th><th scope="col">Long séjour / jour</th><th scope="col">Journée seule</th><th scope="col">Carte 10 + <?= (int) $t['cartes']['offertes10'] ?></th><th scope="col">Carte 20 + <?= (int) $t['cartes']['offertes20'] ?></th></tr></thead>
      <tbody>
      <?php foreach ($t['gabarits'] as $g): $id = h($g['id']); ?>
        <tr>
          <th scope="row"><?= h($g['nom']) ?><small><?= h($g['description']) ?></small></th>
          <?php foreach (['prix' => 'prix', 'long' => 'prixLongSejour', 'journee' => 'prixJournee', 'carte10' => 'prixCarte10', 'carte20' => 'prixCarte20'] as $field => $key): ?>
          <td><label class="money"><span class="sr-only"><?= h($g['nom'] . ' ' . $field) ?></span><input type="text" inputmode="decimal" name="<?= $field ?>_<?= $id ?>" value="<?= h($g[$key] ?? '') ?>" placeholder="à fixer" maxlength="12"> €</label></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="inline-fields">
    <label>Tarif long séjour au-delà de<span><input type="number" name="seuilJours" value="<?= (int) $t['seuilJours'] ?>" min="1" max="60"> jours</span></label>
    <label>Journées offertes carte 10<input type="number" name="offertes10" value="<?= (int) $t['cartes']['offertes10'] ?>" min="0" max="10"></label>
    <label>Journées offertes carte 20<input type="number" name="offertes20" value="<?= (int) $t['cartes']['offertes20'] ?>" min="0" max="20"></label>
    <label>Validité des cartes<span><input type="number" name="validiteMois" value="<?= (int) $t['cartes']['validiteMois'] ?>" min="1" max="36"> mois</span></label>
  </div>
  <button type="submit" class="btn">Enregistrer les tarifs</button>
</form>
<?php page_end();
