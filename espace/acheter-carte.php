<?php
/* Le client achète une carte journée : il choisit la carte, puis paie par virement ou sur place. */
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];
$tarifs = load_tarifs();
$errors = [];

$dogs = user_dogs($uid);
$sizes = array_values(array_unique(array_column($dogs, 'size')));
if (!$sizes) {
    $sizes = array_keys(SIZES);
}
$choice = post('carte', 20);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$size, $type] = array_pad(explode(':', $choice, 2), 2, '');
    [$err, $cardId] = request_card($user, $size, (int) $type);
    if ($err) {
        $errors[] = $err;
    } else {
        flash('success', 'Votre demande de carte est envoyée. Voici comment la régler.');
        redirect('/espace/carte.php#en-attente');
    }
}

page_start('Acheter une carte journée', 'client', 'carte');
?>
<p class="eyebrow"><a href="/espace/carte.php">Mes cartes</a></p>
<h1>Acheter une carte journée</h1>
<p class="lead">Chaque carte sert pour vos chiens du même gabarit. Elle est valable <?= (int) $tarifs['cartes']['validiteMois'] ?> mois à partir de son activation par la pension.</p>
<?php errors_box($errors); ?>
<form method="post" class="form form--wide buycard">
  <?= csrf_field() ?>
  <?php foreach ($sizes as $size): $names = array_column(array_filter($dogs, static fn($d) => $d['size'] === $size), 'name'); ?>
  <fieldset class="buycard__group">
    <legend><?= h(size_label($size)) ?><?= $names ? ' <span class="muted">· ' . h(implode(', ', $names)) . '</span>' : '' ?></legend>
    <div class="buycard__options">
    <?php foreach ([10, 20] as $type):
        $price = card_price($size, $type);
        $free = (int) $tarifs['cartes']['offertes' . $type];
        $value = $size . ':' . $type; ?>
      <label class="buycard__opt buycard__opt--<?= $type ?><?= $price === null ? ' is-off' : '' ?>">
        <input type="radio" name="carte" value="<?= h($value) ?>" required<?= checked($choice === $value) ?><?= $price === null ? ' disabled' : '' ?>>
        <span class="buycard__face">
          <span class="buycard__days"><?= $type ?><small> journées</small></span>
          <span class="buycard__free">+ <?= $free ?> offerte<?= $free > 1 ? 's' : '' ?></span>
          <span class="buycard__price"><?= $price === null ? 'Prix bientôt disponible' : h(euros($price)) ?></span>
          <?php if ($price !== null): ?><span class="buycard__per">soit <?= h(euros(round($price / ($type + $free), 2))) ?> la journée</span><?php endif; ?>
        </span>
      </label>
    <?php endforeach; ?>
    </div>
  </fieldset>
  <?php endforeach; ?>
  <div class="card buycard__how">
    <h2>Comment ça se passe ?</h2>
    <ol class="steps">
      <li><strong>Vous choisissez votre carte</strong> et la demandez ici.</li>
      <li><strong>Vous la réglez</strong> par virement (le numéro de compte s'affiche juste après) ou sur place.</li>
      <li><strong>La pension l'active</strong> : vous recevez un e-mail et vous pouvez réserver vos journées.</li>
    </ol>
  </div>
  <button type="submit" class="btn">Demander cette carte</button>
</form>
<?php page_end(['/js/espace.js']);
