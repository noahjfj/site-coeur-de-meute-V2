<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();

$id = query_int('id') ?: post_int('user_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action', 20);
    if ($action === 'upload') {
        $err = store_contract((int) $me['id'], $id, post('title', 190), $_FILES['pdf'] ?? []);
        flash($err ? 'error' : 'success', $err ?? 'Le document est déposé et le client a été prévenu.');
    } elseif ($action === 'delete_contract') {
        $c = q_one('SELECT * FROM contracts WHERE id = ? AND user_id = ?', [post_int('contract_id'), $id]);
        if ($c) {
            @unlink(contract_path($c));
            q('DELETE FROM contracts WHERE id = ?', [$c['id']]);
            audit((int) $me['id'], 'contract_deleted', (string) $c['id']);
            flash('success', 'Document supprimé.');
        }
    }
    redirect('/gerante/clients.php?id=' . $id);
}

if ($id) {
    $client = q_one("SELECT * FROM users WHERE id = ? AND role = 'client'", [$id]);
    if (!$client) {
        http_response_code(404);
        exit('Client introuvable.');
    }
    $dogs = user_dogs($id);
    $requests = q_all('SELECT r.*, d.name AS dog FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.user_id = ? ORDER BY r.start_date DESC LIMIT 50', [$id]);
    $cards = q_all('SELECT * FROM cards WHERE user_id = ? ORDER BY expires_at DESC', [$id]);
    $contracts = q_all('SELECT * FROM contracts WHERE user_id = ? ORDER BY created_at DESC', [$id]);

    page_start($client['first_name'] . ' ' . $client['last_name'], 'gerante', 'clients');
    ?>
    <p class="eyebrow"><a href="/gerante/clients.php">Clients</a></p>
    <h1><?= h($client['first_name'] . ' ' . $client['last_name']) ?></h1>
    <p class="muted"><?= h($client['email']) ?><?= $client['phone'] ? ' · ' . h($client['phone']) : '' ?><?= $client['address'] ? ' · ' . h($client['address']) : '' ?><?= $client['email_verified_at'] ? '' : ' · e-mail non confirmé' ?></p>

    <section class="section">
      <h2>Chiens</h2>
      <?php if (!$dogs): ?><p class="muted">Aucun chien enregistré.</p><?php endif; ?>
      <div class="dogs">
      <?php foreach ($dogs as $d): ?>
        <article class="card dog">
          <div class="dog__title"><h3><?= h($d['name']) ?></h3><span><?= h(implode(' · ', array_filter([$d['breed'], dog_age($d['birth_date']), size_label($d['size']), $d['sterilized'] ? 'stérilisé(e)' : '']))) ?></span></div>
          <dl class="facts">
            <div><dt>Vaccins jusqu'au</dt><dd class="<?= $d['vaccines_until'] && $d['vaccines_until'] >= today() ? 'ok' : 'warn' ?>"><?= $d['vaccines_until'] ? h(date_fr($d['vaccines_until'])) : 'Non renseigné' ?></dd></div>
            <div><dt>Vétérinaire</dt><dd><?= h($d['vet'] ?: '—') ?></dd></div>
            <div><dt>Santé</dt><dd><?= nl2br(h($d['health'] ?: '—')) ?></dd></div>
            <div><dt>Caractère</dt><dd><?= nl2br(h($d['temperament'] ?: '—')) ?></dd></div>
          </dl>
        </article>
      <?php endforeach; ?>
      </div>
    </section>

    <section class="section">
      <div class="section__head"><h2>Cartes journée</h2><a class="btn btn--small" href="/gerante/cartes.php?client=<?= (int) $id ?>">+ Activer une carte</a></div>
      <?php if (!$cards): ?><p class="muted">Aucune carte.</p><?php else: ?>
      <ul class="plain">
        <?php foreach ($cards as $c): ?>
          <li>Carte <?= (int) $c['paid_days'] ?> + <?= (int) $c['free_days'] ?> (<?= h(size_label($c['size'])) ?>, <?= h(euros($c['price_paid'])) ?>) · <?= card_remaining($c) ?> / <?= card_total($c) ?> restantes · jusqu'au <?= h(date_fr($c['expires_at'])) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="section">
      <h2>Demandes</h2>
      <?php if (!$requests): ?><p class="muted">Aucune demande.</p><?php else: ?>
      <div class="table-box"><table class="table">
        <thead><tr><th scope="col">Dates</th><th scope="col">Chien</th><th scope="col">Type</th><th scope="col">État</th></tr></thead>
        <tbody><?php foreach ($requests as $r): ?>
          <tr><td><?= h(request_label($r)) ?></td><td><?= h($r['dog']) ?></td><td><?= $r['kind'] === 'journee' ? 'Journée' : 'Séjour' ?></td><td><?= status_badge($r['status']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </section>

    <section class="section">
      <h2>Contrats et documents</h2>
      <?php foreach ($contracts as $c): ?>
        <div class="card doc">
          <div class="doc__text"><strong><?= h($c['title']) ?></strong><span>Ajouté le <?= h(date_fr(substr($c['created_at'], 0, 10))) ?></span></div>
          <a href="/espace/contrat.php?id=<?= (int) $c['id'] ?>">Télécharger</a>
          <form method="post" data-confirm="Supprimer ce document ?">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $id ?>">
            <input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
            <button type="submit" name="action" value="delete_contract" class="linklike">Supprimer</button>
          </form>
        </div>
      <?php endforeach; ?>
      <form method="post" enctype="multipart/form-data" class="card form">
        <h3>Déposer un contrat (PDF, 10 Mo maximum)</h3>
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int) $id ?>">
        <input type="hidden" name="action" value="upload">
        <label>Titre<input type="text" name="title" maxlength="190" placeholder="Ex. : Contrat séjour Max, 12 → 19 déc. 2026"></label>
        <label>Fichier PDF<input type="file" name="pdf" accept="application/pdf" required></label>
        <button type="submit" class="btn">Déposer</button>
      </form>
    </section>
    <?php
    page_end(['/js/espace.js']);
    exit;
}

$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
$clients = q_all("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS dogs
    FROM users u LEFT JOIN dogs d ON d.user_id = u.id
    WHERE u.role = 'client' AND (? = '' OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR d.name LIKE ?)
    GROUP BY u.id ORDER BY u.last_name, u.first_name LIMIT 300", [$search, $like, $like, $like, $like]);

page_start('Clients', 'gerante', 'clients');
?>
<div class="section__head">
  <h1>Clients et chiens</h1>
  <form method="get" class="search"><label class="sr-only" for="q">Rechercher</label><input type="search" id="q" name="q" value="<?= h($search) ?>" placeholder="Nom, e-mail ou chien"><button class="btn btn--small" type="submit">Rechercher</button></form>
</div>
<div class="table-box">
  <table class="table">
    <thead><tr><th scope="col">Client</th><th scope="col">Chiens</th><th scope="col">Contact</th></tr></thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr><td><a href="/gerante/clients.php?id=<?= (int) $c['id'] ?>"><strong><?= h($c['last_name'] . ' ' . $c['first_name']) ?></strong></a></td><td><?= h($c['dogs'] ?? '—') ?></td><td class="muted"><?= h($c['email']) ?><?= $c['phone'] ? ' · ' . h($c['phone']) : '' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$clients): ?><tr><td colspan="3" class="muted">Aucun client trouvé.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php page_end();
