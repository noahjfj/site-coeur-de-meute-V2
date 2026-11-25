<?php
/*
 * Historique de toutes les demandes (en attente, confirmées, refusées, annulées), avec filtres.
 * Les filtres passent dans l'adresse (GET) : on peut garder une recherche en favori.
 */
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();

const PER_PAGE = 50;
$f = [
    'statut' => is_string($_GET['statut'] ?? null) && isset(STATUS_LABELS[$_GET['statut']]) ? $_GET['statut'] : '',
    'type'   => in_array($_GET['type'] ?? '', ['sejour', 'journee'], true) ? $_GET['type'] : '',
    'q'      => is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '',
    'du'     => valid_date(is_string($_GET['du'] ?? null) ? $_GET['du'] : '') ?? '',
    'au'     => valid_date(is_string($_GET['au'] ?? null) ? $_GET['au'] : '') ?? '',
    'paiement' => in_array($_GET['paiement'] ?? '', ['attendu', 'acompte', 'paye'], true) ? $_GET['paiement'] : '',
];
$page = max(1, query_int('page'));

$where = ['1 = 1'];
$params = [];
if ($f['statut'] !== '') {
    $where[] = 'r.status = ?';
    $params[] = $f['statut'];
}
if ($f['type'] !== '') {
    $where[] = 'r.kind = ?';
    $params[] = $f['type'];
}
if ($f['q'] !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
    $where[] = "(d.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    array_push($params, $like, $like, $like, $like, $like);
}
// Période : les demandes dont les dates touchent la période choisie
if ($f['du'] !== '') {
    $where[] = 'r.end_date >= ?';
    $params[] = $f['du'];
}
if ($f['au'] !== '') {
    $where[] = 'r.start_date <= ?';
    $params[] = $f['au'];
}
// Paiement : acompte pas encore reçu / acompte reçu (reste à payer) / tout payé
if ($f['paiement'] !== '') {
    $where[] = [
        'attendu' => "r.kind = 'sejour' AND r.price_total IS NOT NULL AND r.deposit_paid_at IS NULL AND r.paid_full_at IS NULL AND r.status IN ('pending','confirmed')",
        'acompte' => "r.kind = 'sejour' AND r.deposit_paid_at IS NOT NULL AND r.paid_full_at IS NULL",
        'paye'    => "r.kind = 'sejour' AND r.paid_full_at IS NOT NULL",
    ][$f['paiement']];
}
$from = 'FROM stay_requests r JOIN dogs d ON d.id = r.dog_id JOIN users u ON u.id = r.user_id WHERE ' . implode(' AND ', $where);

$total = (int) q('SELECT COUNT(*) ' . $from, $params)->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = min($page, $pages);
$rows = q_all('SELECT r.*, d.name AS dog, d.size, u.first_name, u.last_name, u.id AS uid ' . $from
    . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ' . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE), $params);

$counts = [];
foreach (q_all('SELECT status, COUNT(*) AS n FROM stay_requests GROUP BY status') as $c) {
    $counts[$c['status']] = (int) $c['n'];
}

function filter_url(array $f, array $change): string
{
    $q = array_filter(array_merge($f, $change), static fn($v) => $v !== '' && $v !== null && $v !== 1);
    return '/gerante/historique.php' . ($q ? '?' . http_build_query($q) : '');
}

$back = filter_url($f, ['page' => $page]);
page_start('Historique des demandes', 'gerante', 'historique');
?>
<section class="card section">
  <div class="section__head">
    <h1>Historique des demandes</h1>
    <span class="muted"><?= $total ?> demande<?= $total > 1 ? 's' : '' ?></span>
  </div>

  <nav class="chips" aria-label="Filtrer par état">
    <a class="chip" href="<?= h(filter_url($f, ['statut' => '', 'page' => 1])) ?>"<?= $f['statut'] === '' ? ' aria-current="true"' : '' ?>>Toutes <span><?= array_sum($counts) ?></span></a>
    <?php foreach (STATUS_LABELS as $k => $label): ?>
      <a class="chip chip--<?= h($k) ?>" href="<?= h(filter_url($f, ['statut' => $k, 'page' => 1])) ?>"<?= $f['statut'] === $k ? ' aria-current="true"' : '' ?>><?= h($label) ?> <span><?= $counts[$k] ?? 0 ?></span></a>
    <?php endforeach; ?>
  </nav>

  <details class="filterbox" open data-filterbox<?= ($f['q'] !== '' || $f['type'] !== '' || $f['du'] !== '' || $f['au'] !== '' || $f['paiement'] !== '') ? ' data-active' : '' ?>>
  <summary class="filterbox__toggle">Rechercher et filtrer</summary>
  <form method="get" class="filters">
    <?php if ($f['statut'] !== ''): ?><input type="hidden" name="statut" value="<?= h($f['statut']) ?>"><?php endif; ?>
    <label class="filters__q">Client ou chien<input type="search" name="q" value="<?= h($f['q']) ?>" placeholder="Nom, e-mail ou chien"></label>
    <label>Type
      <select name="type">
        <option value="">Tous</option>
        <option value="sejour"<?= selected($f['type'] === 'sejour') ?>>Séjours</option>
        <option value="journee"<?= selected($f['type'] === 'journee') ?>>Journées</option>
      </select></label>
    <label>Paiement
      <select name="paiement">
        <option value="">Tous</option>
        <option value="attendu"<?= selected($f['paiement'] === 'attendu') ?>>Acompte à recevoir</option>
        <option value="acompte"<?= selected($f['paiement'] === 'acompte') ?>>Acompte reçu, reste à payer</option>
        <option value="paye"<?= selected($f['paiement'] === 'paye') ?>>Tout payé</option>
      </select></label>
    <label>Du<input type="date" name="du" value="<?= h($f['du']) ?>"></label>
    <label>Au<input type="date" name="au" value="<?= h($f['au']) ?>"></label>
    <div class="filters__actions">
      <button type="submit" class="btn btn--small">Filtrer</button>
      <?php if (array_filter($f)): ?><a class="linklike" href="/gerante/historique.php">Effacer</a><?php endif; ?>
    </div>
  </form>
  </details>

  <?php if (!$rows): ?>
    <p class="muted">Aucune demande ne correspond à ces filtres.</p>
  <?php else: ?>
  <div class="table-box">
    <table class="table">
      <thead><tr><th scope="col">Dates</th><th scope="col">Chien</th><th scope="col">Client</th><th scope="col">Type</th><th scope="col">État</th><th scope="col">Paiement</th><th scope="col">Messages</th><th scope="col">Demandé le</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h(request_label($r)) ?></td>
          <td><strong><?= h($r['dog']) ?></strong> <span class="muted">(<?= h(size_label($r['size'])) ?>)</span></td>
          <td><a href="/gerante/clients.php?id=<?= (int) $r['uid'] ?>"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></a></td>
          <td><?= $r['kind'] === 'journee' ? 'Journée' : 'Séjour' ?></td>
          <td><?= status_badge($r['status']) ?><?php if ($r['decided_at']): ?><small class="muted block">le <?= h(date_fr(substr($r['decided_at'], 0, 10))) ?></small><?php endif; ?></td>
          <td><?= payment_toggles($r, $back) ?></td>
          <td class="small"><?php if ($r['note']): ?><span class="block">Client : « <?= h($r['note']) ?> »</span><?php endif; ?><?php if ($r['manager_message']): ?><span class="block muted">Vous : « <?= h($r['manager_message']) ?> »</span><?php endif; ?><?= !$r['note'] && !$r['manager_message'] ? '—' : '' ?></td>
          <td><?= h(date_fr(substr($r['created_at'], 0, 10))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Pages">
    <?php if ($page > 1): ?><a class="btn btn--ghost btn--small" href="<?= h(filter_url($f, ['page' => $page - 1])) ?>">← Plus récentes</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> sur <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn--ghost btn--small" href="<?= h(filter_url($f, ['page' => $page + 1])) ?>">Plus anciennes →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php page_end(['/js/espace.js']);
