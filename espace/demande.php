<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];
$dogs = user_dogs($uid);
$tarifs = load_tarifs();

$errors = [];
$kind = 'sejour';
$mode = 'same';
$chosen = [];
$sameFrom = $sameTo = '';
$from = $to = [];
$note = '';

// Arrivée depuis une carte journée : « Journée » déjà choisi, et les chiens du gabarit de la carte cochés
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['kind'] ?? '') === 'journee') {
    $kind = 'journee';
    $sizeFromCard = is_string($_GET['size'] ?? null) && isset(SIZES[$_GET['size']]) ? $_GET['size'] : null;
    foreach ($dogs as $d) {
        if ($sizeFromCard === null || $d['size'] === $sizeFromCard) {
            $chosen[] = (int) $d['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kind = post('kind', 10) === 'journee' ? 'journee' : 'sejour';
    $mode = post('mode', 10) === 'diff' ? 'diff' : 'same';
    $note = post('note', 1000);
    $sameFrom = post('same_from', 10);
    $sameTo = post('same_to', 10);
    $rawDogs = $_POST['dogs'] ?? [];
    $chosen = is_array($rawDogs) ? array_values(array_unique(array_map('intval', array_filter($rawDogs, 'is_string')))) : [];
    $from = is_array($_POST['from'] ?? null) ? $_POST['from'] : [];
    $to = is_array($_POST['to'] ?? null) ? $_POST['to'] : [];

    $items = [];
    foreach ($chosen as $dogId) {
        if ($mode === 'diff' && count($chosen) > 1) {
            $items[] = [$dogId, is_string($from[$dogId] ?? null) ? $from[$dogId] : '', is_string($to[$dogId] ?? null) ? $to[$dogId] : ''];
        } else {
            $items[] = [$dogId, $sameFrom, $sameTo];
        }
    }
    $errors = create_requests($user, $kind, $items, $note);
    if (!$errors) {
        flash('success', count($items) > 1 ? 'Vos demandes sont envoyées. La pension vous répond par e-mail.' : 'Votre demande est envoyée. La pension vous répond par e-mail.');
        redirect('/espace/reservations.php');
    }
}

$cardDays = available_days_by_size($uid);
$hasCard = array_sum($cardDays);
$cardText = [];
foreach (SIZES as $sid => $_) {
    if (isset($cardDays[$sid])) {
        $cardText[] = $cardDays[$sid] . ' ' . mb_strtolower(size_label($sid));
    }
}
$priceData = [];
foreach ($tarifs['gabarits'] as $g) {
    $priceData[$g['id']] = [$g['prix'], $g['prixLongSejour']];
}

page_start('Demander un séjour', 'client', 'demande');
?>
<p class="eyebrow">Nouvelle demande</p>
<h1>Demander un séjour</h1>
<?php if (!$dogs): ?>
  <div class="card empty">
    <p>Ajoutez d'abord la fiche de votre chien, puis revenez ici.</p>
    <a class="btn" href="/espace/chien.php">Ajouter un chien</a>
  </div>
<?php else: ?>
<?php errors_box($errors); ?>
<form method="post" class="request" id="request-form" data-seuil="<?= (int) $tarifs['seuilJours'] ?>" data-prices="<?= h(json_encode($priceData)) ?>" data-cards="<?= h(json_encode((object) $cardDays)) ?>">
  <?= csrf_field() ?>
  <div class="request__main">
    <fieldset class="choices">
      <legend>1. Type de garde</legend>
      <label class="choice"><input type="radio" name="kind" value="sejour"<?= checked($kind === 'sejour') ?>><span><strong>Séjour en pension</strong><small>Avec nuits sur place</small></span></label>
      <label class="choice"><input type="radio" name="kind" value="journee"<?= checked($kind === 'journee') ?>><span><strong>Journée</strong><small><?= $hasCard > 0 ? 'Avec ma carte : ' . h(implode(', ', $cardText)) : 'Nécessite une carte journée' ?></small></span></label>
    </fieldset>

    <fieldset class="choices">
      <legend>2. Quels chiens ?</legend>
      <?php foreach ($dogs as $d): ?>
        <label class="choice"><input type="checkbox" name="dogs[]" value="<?= (int) $d['id'] ?>" data-size="<?= h($d['size']) ?>" data-name="<?= h($d['name']) ?>"<?= checked(in_array((int) $d['id'], $chosen, true) || count($dogs) === 1) ?>>
          <span><strong><?= h($d['name']) ?></strong><small><?= h(size_label($d['size'])) ?></small></span></label>
      <?php endforeach; ?>
    </fieldset>

    <fieldset class="dates">
      <legend>3. Dates</legend>
      <?php if (count($dogs) > 1): ?>
      <div class="segmented" data-only-multi>
        <label><input type="radio" name="mode" value="same"<?= checked($mode === 'same') ?>><span>Mêmes dates pour tous</span></label>
        <label><input type="radio" name="mode" value="diff"<?= checked($mode === 'diff') ?>><span>Dates différentes par chien</span></label>
      </div>
      <?php endif; ?>
      <div class="card datebox" data-dates="same">
        <div class="grid2">
          <label><span data-label-from>Date d'arrivée</span><input type="date" name="same_from" value="<?= h($sameFrom) ?>" min="<?= today() ?>"></label>
          <label data-only-sejour>Date de départ<input type="date" name="same_to" value="<?= h($sameTo) ?>" min="<?= today() ?>"></label>
        </div>
      </div>
      <?php if (count($dogs) > 1): foreach ($dogs as $d): $did = (int) $d['id']; ?>
      <div class="card datebox" data-dates="diff" data-dog="<?= $did ?>">
        <strong><?= h($d['name']) ?></strong>
        <div class="grid2">
          <label><span data-label-from>Date d'arrivée</span><input type="date" name="from[<?= $did ?>]" value="<?= h(is_string($from[$did] ?? null) ? $from[$did] : '') ?>" min="<?= today() ?>"></label>
          <label data-only-sejour>Date de départ<input type="date" name="to[<?= $did ?>]" value="<?= h(is_string($to[$did] ?? null) ? $to[$did] : '') ?>" min="<?= today() ?>"></label>
        </div>
      </div>
      <?php endforeach; endif; ?>
      <label>Un mot pour la pension (facultatif)<textarea name="note" rows="3" maxlength="1000"><?= h($note) ?></textarea></label>
    </fieldset>
  </div>

  <aside class="card summary">
    <h2>Récapitulatif</h2>
    <div id="summary-lines" class="summary__lines" aria-live="polite"><p class="muted">Choisissez vos chiens et vos dates.</p></div>
    <button type="submit" class="btn">Envoyer la demande</button>
    <p class="muted small">La pension confirme chaque chien séparément. Vous recevez un e-mail à chaque réponse. Les prix sont indicatifs.</p>
  </aside>
</form>
<?php endif; ?>
<?php page_end(['/js/espace.js']);
