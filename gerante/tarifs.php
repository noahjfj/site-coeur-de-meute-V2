<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$t = load_tarifs();
$pay = load_payment();
$errors = [];
$payErrors = [];

// Réglages de l'acompte et du virement (formulaire séparé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form', 10) === 'paiement') {
    $pct = str_replace(',', '.', post('pourcentage', 6));
    if (!is_numeric($pct) || (float) $pct < 0 || (float) $pct > 100) {
        $payErrors[] = 'Le pourcentage de l\'acompte doit être entre 0 et 100.';
    } else {
        $pay['pourcentage'] = round((float) $pct, 2);
    }
    $ibanRaw = post('iban', 60);
    if ($ibanRaw === '') {
        $pay['iban'] = '';
    } elseif ($iban = clean_iban($ibanRaw)) {
        $pay['iban'] = $iban;
    } else {
        $payErrors[] = 'Ce numéro de compte (IBAN) n\'est pas valide. Vérifiez-le, par exemple BE68 5390 0754 7034.';
    }
    $pay['titulaire'] = post('titulaire', 120);
    $bic = strtoupper(preg_replace('/\s+/', '', post('bic', 20)) ?? '');
    if ($bic !== '' && !preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic)) {
        $payErrors[] = 'Le code BIC n\'est pas valide (8 ou 11 caractères, par exemple GEBABEBB).';
    } else {
        $pay['bic'] = $bic;
    }
    if (!$payErrors) {
        if (save_payment($pay)) {
            audit((int) $me['id'], 'payment_settings_updated');
            flash('success', 'Les réglages de l\'acompte sont enregistrés.');
            redirect('/gerante/tarifs.php#acompte');
        }
        $payErrors[] = 'Impossible d\'enregistrer : le dossier app/storage n\'est pas modifiable sur l\'hébergement.';
    }
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form', 10) !== 'paiement') {
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

<form method="post" class="card section form form--wide" id="acompte">
  <h2>Acompte et virement</h2>
  <p class="muted">Quand un client demande un séjour, il voit à l'écran et reçoit par e-mail le montant de l'acompte à verser et ce numéro de compte. Vous cochez ensuite « Acompte payé » ou « Tout est payé » sur la demande.</p>
  <?php errors_box($payErrors); ?>
  <?= csrf_field() ?>
  <input type="hidden" name="form" value="paiement">
  <div class="grid2">
    <label>Acompte demandé<span class="money"><input type="text" inputmode="decimal" name="pourcentage" value="<?= h(rtrim(rtrim(number_format((float) $pay['pourcentage'], 2, '.', ''), '0'), '.')) ?>" maxlength="6"> % du prix du séjour</span></label>
    <label>Numéro de compte (IBAN)<input type="text" name="iban" value="<?= h($pay['iban'] !== '' ? format_iban($pay['iban']) : '') ?>" placeholder="BE.. .... .... ...." autocomplete="off" maxlength="60"></label>
    <label>Au nom de<input type="text" name="titulaire" value="<?= h($pay['titulaire']) ?>" maxlength="120"></label>
    <label>BIC <span class="hint">(facultatif)</span><input type="text" name="bic" value="<?= h($pay['bic']) ?>" maxlength="20"></label>
  </div>
  <?php if ($pay['iban'] === ''): ?><p class="flash flash--info">Aucun numéro de compte pour l'instant : l'e-mail dira au client que la pension le lui communiquera.</p><?php endif; ?>
  <button type="submit" class="btn">Enregistrer l'acompte</button>
</form>
<?php page_end(['/js/espace.js']);
