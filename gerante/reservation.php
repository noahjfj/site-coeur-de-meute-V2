<?php
/*
 * La gérante crée elle-même une réservation pour un client (séjour, ou journée avec sa carte).
 * La réservation est directement confirmée. Le client peut être prévenu par e-mail.
 */
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$errors = [];

$clientId = query_int('client') ?: post_int('client_id');
$client = $clientId ? q_one("SELECT * FROM users WHERE id = ? AND role = 'client'", [$clientId]) : null;

$kind = 'sejour';
$chosen = [];
$from = $to = $message = '';
$notify = true;
$depositPaid = $fullPaid = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $client) {
    $kind = post('kind', 10) === 'journee' ? 'journee' : 'sejour';
    $rawDogs = $_POST['dogs'] ?? [];
    $chosen = is_array($rawDogs) ? array_values(array_unique(array_map('intval', array_filter($rawDogs, 'is_string')))) : [];
    $from = post('from', 10);
    $to = post('to', 10);
    $message = post('message', 1000);
    $notify = post('notify', 3) === '1';
    $depositPaid = post('deposit_paid', 3) === '1';
    $fullPaid = post('full_paid', 3) === '1';

    $items = array_map(static fn($id) => [$id, $from, $to], $chosen);
    $ids = [];
    $errors = create_requests($client, $kind, $items, '', (int) $me['id'], $ids);
    if (!$errors) {
        $problems = [];
        foreach ($ids as $rid) {
            $err = decide_request((int) $me['id'], $rid, 'confirmed', $message, false);
            if ($err) {
                $problems[] = $err;
            }
        }
        if ($kind === 'sejour' && ($depositPaid || $fullPaid)) {
            set_payment((int) $me['id'], $ids[0], $fullPaid ? 'full' : 'deposit', true);
        }
        $saved = q_all('SELECT r.*, d.name AS dog FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY r.id');
        if ($notify) {
            $lines = array_map(static fn($r) => '- ' . $r['dog'] . ' : ' . ($r['kind'] === 'journee' ? 'journée du ' . date_fr($r['start_date']) . ' (déduite de votre carte)' : 'du ' . date_fr($r['start_date']) . ' au ' . date_fr($r['end_date'])), $saved);
            $unpaid = array_values(array_filter($saved, static fn($r) => $r['kind'] === 'sejour' && $r['status'] === 'confirmed' && !$r['deposit_paid_at'] && !$r['paid_full_at']));
            $deposit = $unpaid ? deposit_text($unpaid) : '';
            send_mail($client['email'], 'Votre réservation chez Coeur de Meute', greet($client)
                . 'La pension a enregistré et confirmé votre réservation :' . "\n" . implode("\n", $lines)
                . ($message !== '' ? "\n\nMessage de la pension : " . $message : '')
                . ($deposit !== '' ? "\n\n" . $deposit : '')
                . "\n\nVoir vos réservations : " . url('/espace/reservations.php'));
        }
        if ($problems) {
            flash('error', implode(' ', array_unique($problems)) . ' Cette réservation reste « en attente » dans vos demandes.');
        } else {
            flash('success', 'La réservation est enregistrée et confirmée' . ($notify ? ' ; le client a reçu un e-mail.' : '.'));
        }
        redirect('/gerante/clients.php?id=' . (int) $client['id']);
    }
}

$clients = $client ? [] : q_all("SELECT id, first_name, last_name, email FROM users WHERE role = 'client' ORDER BY last_name, first_name");
$dogsByClient = [];
if (!$client) {
    foreach (q_all("SELECT d.user_id, d.name, d.size FROM dogs d JOIN users u ON u.id = d.user_id WHERE u.role = 'client' ORDER BY d.name") as $d) {
        $dogsByClient[(int) $d['user_id']][] = $d;
    }
}
$dogs = $client ? user_dogs((int) $client['id']) : [];
$cardDays = $client ? available_days_by_size((int) $client['id']) : [];
$pay = load_payment();

page_start('Nouvelle réservation', 'gerante', 'reservation');
?>
<section class="card section">
  <p class="eyebrow">Nouvelle réservation</p>
<?php if (!$client): ?>
  <h1>Pour quel client ?</h1>
  <p class="muted">Cherchez le client par son nom ou le nom de son chien. Le client doit avoir un compte (il peut le créer depuis le site).</p>
  <form method="get" class="form form--wide">
    <fieldset class="picker" data-picker>
      <legend>Client</legend>
      <input type="search" class="picker__search" data-picker-search placeholder="Rechercher par nom du client ou du chien…" aria-label="Rechercher un client">
      <div class="picker__list">
        <?php foreach ($clients as $c): $cd = $dogsByClient[(int) $c['id']] ?? []; ?>
        <label class="picker__item" data-search="<?= h(mb_strtolower($c['first_name'] . ' ' . $c['last_name'] . ' ' . $c['email'] . ' ' . implode(' ', array_column($cd, 'name')))) ?>">
          <input type="radio" name="client" value="<?= (int) $c['id'] ?>" required>
          <span><strong><?= h($c['last_name'] . ' ' . $c['first_name']) ?></strong>
            <small><?= $cd ? h(implode(', ', array_map(static fn($d) => $d['name'] . ' (' . mb_strtolower(size_label($d['size'])) . ')', $cd))) : 'Pas encore de chien' ?> · <?= h($c['email']) ?></small></span>
        </label>
        <?php endforeach; ?>
        <?php if (!$clients): ?><p class="muted picker__none">Aucun client pour l'instant.</p><?php endif; ?>
        <p class="muted picker__none" data-picker-none hidden>Aucun client ne correspond.</p>
      </div>
    </fieldset>
    <button type="submit" class="btn">Continuer</button>
  </form>
<?php else: ?>
  <h1><?= h($client['first_name'] . ' ' . $client['last_name']) ?></h1>
  <p class="muted"><?= h($client['email']) ?> · <a href="/gerante/reservation.php">Changer de client</a></p>
  <?php errors_box($errors); ?>
  <?php if (!$dogs): ?>
    <p class="flash flash--info">Ce client n'a pas encore de chien enregistré. Il doit d'abord ajouter la fiche de son chien depuis son espace.</p>
  <?php else: ?>
  <form method="post" class="form form--wide" data-booking>
    <?= csrf_field() ?>
    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">

    <fieldset>
      <legend>1. Type de garde</legend>
      <div class="segmented">
        <label><input type="radio" name="kind" value="sejour"<?= checked($kind === 'sejour') ?>><span>Séjour en pension</span></label>
        <label><input type="radio" name="kind" value="journee"<?= checked($kind === 'journee') ?>><span>Journée avec sa carte</span></label>
      </div>
      <p class="hint" data-only-journee<?= $kind === 'sejour' ? ' hidden' : '' ?>>Journées disponibles sur ses cartes :
        <?php if ($cardDays): ?><?= h(implode(', ', array_map(static fn($s, $n) => $n . ' ' . mb_strtolower(size_label($s)), array_keys($cardDays), $cardDays))) ?><?php else: ?>aucune carte active (activez d'abord une carte dans « Cartes journée »)<?php endif; ?>.</p>
    </fieldset>

    <fieldset class="choices">
      <legend>2. Quels chiens ?</legend>
      <?php foreach ($dogs as $d): ?>
      <label class="choice"><input type="checkbox" name="dogs[]" value="<?= (int) $d['id'] ?>"<?= checked(in_array((int) $d['id'], $chosen, true) || count($dogs) === 1) ?>><span><strong><?= h($d['name']) ?></strong><small><?= h(size_label($d['size'])) ?></small></span></label>
      <?php endforeach; ?>
    </fieldset>

    <fieldset>
      <legend>3. Dates</legend>
      <div class="grid2">
        <label><span data-label-from><?= $kind === 'journee' ? 'Jour' : 'Arrivée' ?></span><input type="date" name="from" value="<?= h($from) ?>" required></label>
        <label data-only-sejour<?= $kind === 'journee' ? ' hidden' : '' ?>>Départ<input type="date" name="to" value="<?= h($to) ?>"></label>
      </div>
    </fieldset>

    <fieldset data-only-sejour<?= $kind === 'journee' ? ' hidden' : '' ?>>
      <legend>4. Paiement</legend>
      <p class="hint">Si rien n'est coché, le client reçoit l'acompte à verser (<?= h(rtrim(rtrim(number_format((float) $pay['pourcentage'], 2, ',', ''), '0'), ',')) ?> %) avec le numéro de compte.</p>
      <label class="check"><input type="checkbox" name="deposit_paid" value="1"<?= checked($depositPaid) ?>> L'acompte est déjà payé</label>
      <label class="check"><input type="checkbox" name="full_paid" value="1"<?= checked($fullPaid) ?>> Tout est déjà payé</label>
    </fieldset>

    <label>Message au client <span class="hint">(facultatif)</span><textarea name="message" rows="2" maxlength="1000"><?= h($message) ?></textarea></label>
    <label class="check"><input type="checkbox" name="notify" value="1"<?= checked($notify) ?>> Prévenir le client par e-mail</label>
    <button type="submit" class="btn">Enregistrer la réservation (confirmée)</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</section>
<?php page_end(['/js/espace.js']);
