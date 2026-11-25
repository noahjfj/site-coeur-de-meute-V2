<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = ['confirm' => 'confirmed', 'refuse' => 'refused', 'cancel' => 'cancelled'][post('action', 10)] ?? '';
    $err = decide_request((int) $me['id'], post_int('id'), $decision, post('message', 1000));
    flash($err ? 'error' : 'success', $err ?? 'C\'est fait : le client a reçu un e-mail.');
    redirect('/gerante/');
}

$pending = q_all("SELECT r.*, d.name AS dog, d.size, u.first_name, u.last_name, u.id AS uid FROM stay_requests r JOIN dogs d ON d.id = r.dog_id JOIN users u ON u.id = r.user_id WHERE r.status = 'pending' ORDER BY r.start_date, r.id");
$upcoming = q_all("SELECT r.*, d.name AS dog, d.size, u.first_name, u.last_name, u.id AS uid FROM stay_requests r JOIN dogs d ON d.id = r.dog_id JOIN users u ON u.id = r.user_id WHERE r.status = 'confirmed' AND r.end_date >= ? AND r.start_date <= ? ORDER BY r.start_date, r.id", [today(), (new DateTimeImmutable('today'))->modify('+60 days')->format('Y-m-d')]);

function request_detail(array $r): string
{
    if ($r['kind'] === 'journee') {
        $left = remaining_days((int) $r['uid'], $r['start_date'], $r['size']);
        return 'Journée avec carte ' . mb_strtolower(size_label($r['size'])) . ' · ' . $left . ' journée' . ($left > 1 ? 's' : '') . ' disponible' . ($left > 1 ? 's' : '');
    }
    [$days, $rate, $total] = estimate_stay($r['size'], $r['start_date'], $r['end_date']);
    return 'Séjour · ' . $days . ' jour' . ($days > 1 ? 's' : '') . ' × ' . euros($rate) . ' = ' . euros($total);
}

page_start('Demandes', 'gerante', 'demandes');
?>
<section class="card section">
  <div class="section__head">
    <h1>Demandes à traiter</h1>
    <a class="btn btn--small" href="/gerante/reservation.php">+ Nouvelle réservation</a>
    <span class="muted"><?= $pending ? count($pending) . ' en attente' : 'Tout est traité' ?></span>
  </div>
  <?php if (!$pending): ?><p class="muted">Aucune nouvelle demande.</p><?php endif; ?>
  <div class="reqs">
  <?php foreach ($pending as $r): ?>
    <div class="req">
      <div class="req__who"><strong><?= h($r['dog']) ?> · <?= h(size_label($r['size'])) ?></strong><a href="/gerante/clients.php?id=<?= (int) $r['uid'] ?>"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></a></div>
      <div class="req__when"><strong><?= h(request_label($r)) ?></strong><span class="muted"><?= h(request_detail($r)) ?></span>
        <?php if ($r['note']): ?><span class="req__note">« <?= h($r['note']) ?> »</span><?php endif; ?>
        <?php if ($r['created_by']): ?><span class="muted small">Créée par vous</span><?php endif; ?></div>
      <?php if ($r['kind'] === 'sejour'): ?><div class="req__pay"><?= payment_toggles($r, '/gerante/') ?></div><?php endif; ?>
      <form method="post" class="req__actions">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <label class="sr-only" for="msg<?= (int) $r['id'] ?>">Message au client</label>
        <input type="text" id="msg<?= (int) $r['id'] ?>" name="message" placeholder="Message au client (facultatif)" maxlength="1000">
        <button type="submit" name="action" value="confirm" class="btn btn--ok btn--small">Confirmer</button>
        <button type="submit" name="action" value="refuse" class="btn btn--ghost btn--small">Refuser</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
  <p class="muted small">Le client reçoit un e-mail à chaque réponse. Une journée confirmée est déduite de sa carte.</p>
</section>

<section class="card section">
  <h2>Séjours confirmés (60 prochains jours)</h2>
  <?php if (!$upcoming): ?><p class="muted">Aucun séjour confirmé à venir.</p><?php else: ?>
  <div class="table-box">
    <table class="table">
      <thead><tr><th scope="col">Dates</th><th scope="col">Chien</th><th scope="col">Client</th><th scope="col">Type</th><th scope="col">Paiement</th><th scope="col"><span class="sr-only">Action</span></th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $r): ?>
        <tr>
          <td><?= h(request_label($r)) ?></td>
          <td><strong><?= h($r['dog']) ?></strong> <span class="muted">(<?= h(size_label($r['size'])) ?>)</span></td>
          <td><a href="/gerante/clients.php?id=<?= (int) $r['uid'] ?>"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></a></td>
          <td><?= $r['kind'] === 'journee' ? 'Journée' : 'Séjour' ?></td>
          <td><?= payment_toggles($r, '/gerante/') ?></td>
          <td>
            <form method="post" data-confirm="Annuler ce séjour confirmé ? Le client sera prévenu par e-mail.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button type="submit" name="action" value="cancel" class="linklike">Annuler</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php page_end(['/js/espace.js']);
