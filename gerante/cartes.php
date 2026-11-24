<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$tarifs = load_tarifs();
$errors = [];
$preset = query_int('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = post_int('client_id');
    $type = post_int('type') === 20 ? 20 : 10;
    $size = post('size', 10);
    $purchased = valid_date(post('purchased_at', 10)) ?? '';
    $priceRaw = str_replace(',', '.', post('price', 12));
    $client = q_one("SELECT * FROM users WHERE id = ? AND role = 'client'", [$clientId]);

    if (!$client) {
        $errors[] = 'Choisissez un client.';
    }
    if (!isset(SIZES[$size])) {
        $errors[] = 'Choisissez le gabarit (pour le prix).';
    }
    if ($purchased === '' || $purchased > today()) {
        $errors[] = 'La date d\'achat est invalide.';
    }
    if ($priceRaw === '' && isset(SIZES[$size])) {
        $priceRaw = (string) (tarif_for($size)['prixCarte' . $type] ?? '');
    }
    if (!is_numeric($priceRaw) || (float) $priceRaw < 0 || (float) $priceRaw > 10000) {
        $errors[] = 'Indiquez le prix payé (le prix de cette carte n\'est pas encore fixé dans les tarifs).';
    }
    if (!$errors) {
        $free = (int) $tarifs['cartes']['offertes' . $type];
        $expires = (new DateTimeImmutable($purchased))->modify('+' . (int) $tarifs['cartes']['validiteMois'] . ' months')->format('Y-m-d');
        q('INSERT INTO cards (user_id, paid_days, free_days, size, price_paid, purchased_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [$clientId, $type, $free, $size, round((float) $priceRaw, 2), $purchased, $expires]);
        audit((int) $me['id'], 'card_activated', 'client ' . $clientId . ' carte ' . $type);
        send_mail($client['email'], 'Votre carte journée est activée', greet($client)
            . 'Votre carte ' . $type . ' journées (+ ' . $free . ' offerte' . ($free > 1 ? 's' : '') . ') est active jusqu\'au ' . date_fr($expires) . ".\n\n"
            . 'Pour réserver une journée : ' . url('/espace/demande.php') . "\nPour voir votre carte : " . url('/espace/carte.php'));
        flash('success', 'La carte est activée et le client a été prévenu par e-mail.');
        redirect('/gerante/cartes.php');
    }
}

$clients = q_all("SELECT id, first_name, last_name, email FROM users WHERE role = 'client' ORDER BY last_name, first_name");
$dogsByClient = [];
foreach (q_all("SELECT d.user_id, d.name, d.size FROM dogs d JOIN users u ON u.id = d.user_id WHERE u.role = 'client' ORDER BY d.name") as $d) {
    $dogsByClient[(int) $d['user_id']][] = $d;
}
$selectedClient = $preset ?: post_int('client_id');
$cards = q_all('SELECT c.*, u.first_name, u.last_name, u.id AS uid FROM cards c JOIN users u ON u.id = c.user_id WHERE c.expires_at >= ? AND c.used_days < c.paid_days + c.free_days ORDER BY c.expires_at', [today()]);
$soon = (new DateTimeImmutable('today'))->modify('+1 month')->format('Y-m-d');
$priceHints = [];
foreach ($tarifs['gabarits'] as $g) {
    $priceHints[$g['id']] = ['10' => $g['prixCarte10'], '20' => $g['prixCarte20']];
}

page_start('Cartes journée', 'gerante', 'cartes');
?>
<section class="card section">
  <h1>Cartes journée actives</h1>
  <?php if (!$cards): ?><p class="muted">Aucune carte active.</p><?php else: ?>
  <div class="table-box"><table class="table">
    <thead><tr><th scope="col">Client</th><th scope="col">Carte</th><th scope="col">Restantes</th><th scope="col">Fin de validité</th></tr></thead>
    <tbody>
    <?php foreach ($cards as $c): $left = card_remaining($c); ?>
      <tr>
        <td><a href="/gerante/clients.php?id=<?= (int) $c['uid'] ?>"><strong><?= h($c['first_name'] . ' ' . $c['last_name']) ?></strong></a></td>
        <td><?= (int) $c['paid_days'] ?> + <?= (int) $c['free_days'] ?> <span class="muted">(<?= h(size_label($c['size'])) ?>)</span></td>
        <td class="<?= $left <= 1 ? 'warn' : '' ?>"><?= $left ?> / <?= card_total($c) ?></td>
        <td class="<?= $c['expires_at'] <= $soon ? 'warn' : '' ?>"><?= h(date_fr($c['expires_at'])) ?><?= $c['expires_at'] <= $soon ? ' (bientôt)' : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="card section" id="activer">
  <h2>Activer une carte vendue</h2>
  <p class="muted">La carte est payée sur place ou par virement, puis activée ici. Elle est valable <?= (int) $tarifs['cartes']['validiteMois'] ?> mois à partir de l'achat et sert pour les chiens du client de ce gabarit.</p>
  <?php errors_box($errors); ?>
  <form method="post" class="form form--wide" id="card-form" data-prices="<?= h(json_encode($priceHints)) ?>">
    <?= csrf_field() ?>
    <fieldset class="picker" data-picker>
      <legend>Client</legend>
      <input type="search" class="picker__search" data-picker-search placeholder="Rechercher par nom du client ou du chien…" aria-label="Rechercher un client">
      <div class="picker__list">
        <?php foreach ($clients as $c): $cd = $dogsByClient[(int) $c['id']] ?? []; ?>
        <label class="picker__item" data-search="<?= h(mb_strtolower($c['first_name'] . ' ' . $c['last_name'] . ' ' . $c['email'] . ' ' . implode(' ', array_column($cd, 'name')))) ?>" data-sizes="<?= h(implode(' ', array_unique(array_column($cd, 'size')))) ?>">
          <input type="radio" name="client_id" value="<?= (int) $c['id'] ?>" required<?= checked((int) $c['id'] === $selectedClient) ?>>
          <span><strong><?= h($c['last_name'] . ' ' . $c['first_name']) ?></strong>
            <small><?= $cd ? h(implode(', ', array_map(static fn($d) => $d['name'] . ' (' . mb_strtolower(size_label($d['size'])) . ')', $cd))) : 'Pas encore de chien' ?> · <?= h($c['email']) ?></small></span>
        </label>
        <?php endforeach; ?>
        <p class="muted picker__none" data-picker-none hidden>Aucun client ne correspond.</p>
      </div>
    </fieldset>
    <div class="grid2">
      <label>Carte
        <select name="type">
          <option value="10">10 journées + <?= (int) $tarifs['cartes']['offertes10'] ?> offerte(s)</option>
          <option value="20"<?= selected(post_int('type') === 20) ?>>20 journées + <?= (int) $tarifs['cartes']['offertes20'] ?> offerte(s)</option>
        </select></label>
      <label>Gabarit des chiens
        <select name="size" required>
          <option value="">— Choisir —</option>
          <?php foreach (SIZES as $k => $label): ?><option value="<?= h($k) ?>"<?= selected(post('size', 10) === $k) ?>><?= h($label) ?></option><?php endforeach; ?>
        </select></label>
      <label>Prix payé (€)<input type="text" name="price" inputmode="decimal" maxlength="12" value="<?= h(post('price', 12)) ?>"><span class="hint">Vide = prix des tarifs.</span></label>
      <label>Date d'achat<input type="date" name="purchased_at" value="<?= h(post('purchased_at', 10) ?: today()) ?>" max="<?= today() ?>" required></label>
    </div>
    <button type="submit" class="btn">Activer la carte</button>
  </form>
</section>
<?php page_end(['/js/espace.js']);
