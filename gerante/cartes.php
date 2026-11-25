<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$tarifs = load_tarifs();
$errors = [];
$preset = query_int('client');

// Demandes d'achat des clients et case « payée »
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('action', 20), ['activate', 'activate_paid', 'refuse', 'paid', 'unpaid'], true)) {
    $action = post('action', 20);
    $cardId = post_int('card_id');
    if ($action === 'paid' || $action === 'unpaid') {
        set_card_paid((int) $me['id'], $cardId, $action === 'paid');
        flash('success', $action === 'paid' ? 'Carte notée comme payée.' : 'Paiement retiré.');
    } else {
        $err = decide_card((int) $me['id'], $cardId, $action === 'refuse' ? 'refuse' : 'activate', $action === 'activate_paid');
        flash($err ? 'error' : 'success', $err ?? ($action === 'refuse' ? 'Demande refusée, le client est prévenu.' : 'Carte activée : le client est prévenu et peut réserver ses journées.'));
    }
    redirect('/gerante/cartes.php');
}

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
        q('INSERT INTO cards (user_id, paid_days, free_days, size, price_paid, purchased_at, expires_at, status, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'active\', ' . (post('paid', 3) === '1' ? 'NOW()' : 'NULL') . ')', [$clientId, $type, $free, $size, round((float) $priceRaw, 2), $purchased, $expires]);
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
$requested = q_all("SELECT c.*, u.first_name, u.last_name, u.email, u.id AS uid FROM cards c JOIN users u ON u.id = c.user_id WHERE c.status = 'requested' ORDER BY c.requested_at");
$cards = q_all('SELECT c.*, u.first_name, u.last_name, u.id AS uid FROM cards c JOIN users u ON u.id = c.user_id WHERE c.status = \'active\' AND c.expires_at >= ? AND c.used_days < c.paid_days + c.free_days ORDER BY c.expires_at', [today()]);
$soon = (new DateTimeImmutable('today'))->modify('+1 month')->format('Y-m-d');
$priceHints = [];
foreach ($tarifs['gabarits'] as $g) {
    $priceHints[$g['id']] = ['10' => $g['prixCarte10'], '20' => $g['prixCarte20']];
}

page_start('Cartes journée', 'gerante', 'cartes');
?>
<?php if ($requested): ?>
<section class="card section">
  <div class="section__head"><h1>Demandes d'achat</h1><span class="muted"><?= count($requested) ?> en attente</span></div>
  <div class="reqs">
  <?php foreach ($requested as $c): ?>
    <div class="req">
      <div class="req__who"><strong>Carte <?= (int) $c['paid_days'] ?> + <?= (int) $c['free_days'] ?> · <?= h(size_label($c['size'])) ?></strong><a href="/gerante/clients.php?id=<?= (int) $c['uid'] ?>"><?= h($c['first_name'] . ' ' . $c['last_name']) ?></a></div>
      <div class="req__when"><strong><?= h(euros($c['price_paid'])) ?></strong><span class="muted">Demandée le <?= h(date_fr(substr((string) $c['requested_at'], 0, 10))) ?></span></div>
      <form method="post" class="req__actions req__actions--cards">
        <?= csrf_field() ?>
        <input type="hidden" name="card_id" value="<?= (int) $c['id'] ?>">
        <button type="submit" name="action" value="activate_paid" class="btn btn--ok btn--small">Activer · payée</button>
        <button type="submit" name="action" value="activate" class="btn btn--small">Activer · pas encore payée</button>
        <button type="submit" name="action" value="refuse" class="btn btn--ghost btn--small" formnovalidate>Refuser</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
  <p class="muted small">Vous pouvez activer la carte sans attendre le virement (il met parfois quelques jours) et cocher « Payée » plus tard.</p>
</section>
<?php endif; ?>

<section class="card section">
  <h1>Cartes journée actives</h1>
  <?php if (!$cards): ?><p class="muted">Aucune carte active.</p><?php else: ?>
  <div class="table-box"><table class="table">
    <thead><tr><th scope="col">Client</th><th scope="col">Carte</th><th scope="col">Restantes</th><th scope="col">Fin de validité</th><th scope="col">Paiement</th></tr></thead>
    <tbody>
    <?php foreach ($cards as $c): $left = card_remaining($c); ?>
      <tr>
        <td><a href="/gerante/clients.php?id=<?= (int) $c['uid'] ?>"><strong><?= h($c['first_name'] . ' ' . $c['last_name']) ?></strong></a></td>
        <td><?= (int) $c['paid_days'] ?> + <?= (int) $c['free_days'] ?> <span class="muted">(<?= h(size_label($c['size'])) ?>)</span></td>
        <td class="<?= $left <= 1 ? 'warn' : '' ?>"><?= $left ?> / <?= card_total($c) ?></td>
        <td class="<?= $c['expires_at'] <= $soon ? 'warn' : '' ?>"><?= h(date_fr($c['expires_at'])) ?><?= $c['expires_at'] <= $soon ? ' (bientôt)' : '' ?></td>
        <td><form method="post" class="pay"><?= csrf_field() ?><input type="hidden" name="card_id" value="<?= (int) $c['id'] ?>">
          <button type="submit" name="action" value="<?= $c['paid_at'] ? 'unpaid' : 'paid' ?>" class="pay__btn<?= $c['paid_at'] ? ' is-on' : '' ?>" aria-pressed="<?= $c['paid_at'] ? 'true' : 'false' ?>"><span class="pay__box" aria-hidden="true"></span><span>Payée <small><?= h(euros($c['price_paid'])) ?></small></span></button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="card section" id="activer">
  <h2>Activer une carte vendue sur place</h2>
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
    <label class="check"><input type="checkbox" name="paid" value="1"<?= checked($_SERVER['REQUEST_METHOD'] !== 'POST' || post('paid', 3) === '1') ?>> La carte est déjà payée</label>
    <button type="submit" class="btn">Activer la carte</button>
  </form>
</section>
<?php page_end(['/js/espace.js']);
