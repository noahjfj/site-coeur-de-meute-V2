<?php
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();

$id = query_int('id') ?: post_int('user_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action', 20);
    if ($action === 'upload') {
        // La photo prise avec le téléphone passe en premier ; sinon le fichier choisi (PDF ou image)
        $file = ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $_FILES['photo'] : ($_FILES['pdf'] ?? []);
        $err = store_contract((int) $me['id'], $id, post('title', 190), $file, post_int('request_id') ?: null);
        flash($err ? 'error' : 'success', $err ?? 'Le document est déposé et le client a été prévenu.');
        redirect('/gerante/clients.php?id=' . $id . '#contrats');
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
    $cards = q_all('SELECT * FROM cards WHERE user_id = ? ORDER BY status = \'requested\' DESC, expires_at DESC', [$id]);
    $contracts = q_all('SELECT c.*, r.kind, r.start_date, r.end_date, d.name AS dog FROM contracts c LEFT JOIN stay_requests r ON r.id = c.request_id LEFT JOIN dogs d ON d.id = r.dog_id WHERE c.user_id = ? ORDER BY c.created_at DESC', [$id]);
    $contractsByRequest = [];
    foreach ($contracts as $c) {
        if ($c['request_id']) {
            $contractsByRequest[(int) $c['request_id']][] = $c;
        }
    }
    $presetResa = query_int('resa');

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
          <div class="dog__head">
          <?php if ($photo = dog_photo_url($d)): ?><a class="dog__avatar dog__avatar--photo" href="<?= h($photo) ?>" target="_blank" rel="noopener"><img src="<?= h($photo) ?>" alt="Photo de <?= h($d['name']) ?>" width="72" height="72" loading="lazy"></a><?php endif; ?>
          <div class="dog__title"><h3><?= h($d['name']) ?></h3><span><?= h(implode(' · ', array_filter([$d['breed'], dog_age($d['birth_date']), size_label($d['size']), $d['sterilized'] ? 'stérilisé(e)' : '']))) ?></span></div>
          </div>
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
          <li>Carte <?= (int) $c['paid_days'] ?> + <?= (int) $c['free_days'] ?> (<?= h(size_label($c['size'])) ?>, <?= h(euros($c['price_paid'])) ?>) · <?php if ($c['status'] === 'requested'): ?><a href="/gerante/cartes.php">demande d'achat à activer</a><?php else: ?><?= card_remaining($c) ?> / <?= card_total($c) ?> restantes · jusqu'au <?= h(date_fr($c['expires_at'])) ?><?= $c['paid_at'] ? '' : ' · <strong class="warn">pas encore payée</strong>' ?><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="section">
      <div class="section__head"><h2>Réservations</h2><a class="btn btn--small" href="/gerante/reservation.php?client=<?= (int) $id ?>">+ Nouvelle réservation</a></div>
      <?php if (!$requests): ?><p class="muted">Aucune demande.</p><?php else: ?>
      <div class="table-box"><table class="table">
        <thead><tr><th scope="col">Dates</th><th scope="col">Chien</th><th scope="col">Type</th><th scope="col">État</th><th scope="col">Paiement</th><th scope="col">Contrat</th></tr></thead>
        <tbody><?php foreach ($requests as $r): ?>
          <tr><td><?= h(request_label($r)) ?></td><td><?= h($r['dog']) ?></td><td><?= $r['kind'] === 'journee' ? 'Journée' : 'Séjour' ?></td><td><?= status_badge($r['status']) ?></td><td><?= payment_toggles($r, '/gerante/clients.php?id=' . (int) $id) ?></td>
            <td><?php foreach ($contractsByRequest[(int) $r['id']] ?? [] as $c): ?><a class="block" href="/espace/contrat.php?id=<?= (int) $c['id'] ?>"><?= ($c['ext'] ?? 'pdf') === 'jpg' ? 'Photo' : 'PDF' ?> · <?= h($c['title']) ?></a><?php endforeach; ?>
              <a class="block small" href="/gerante/clients.php?id=<?= (int) $id ?>&amp;resa=<?= (int) $r['id'] ?>#contrats">+ Ajouter un contrat</a></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </section>

    <section class="section" id="contrats">
      <h2>Contrats et documents</h2>
      <?php if (!$contracts): ?><p class="muted">Aucun document pour l'instant.</p><?php endif; ?>
      <div class="docs">
      <?php foreach ($contracts as $c): ?>
        <div class="card doc">
          <span class="doc__icon" aria-hidden="true"><?= icon(($c['ext'] ?? 'pdf') === 'jpg' ? 'camera' : 'doc') ?></span>
          <div class="doc__text"><strong><?= h($c['title']) ?></strong>
            <span><?= $c['request_id'] && $c['start_date'] ? h(($c['kind'] === 'journee' ? 'Journée du ' . date_fr($c['start_date']) : 'Séjour ' . request_label($c)) . ' · ' . $c['dog']) . ' · ' : '' ?>ajouté le <?= h(date_fr(substr($c['created_at'], 0, 10))) ?></span></div>
          <a href="/espace/contrat.php?id=<?= (int) $c['id'] ?>">Ouvrir</a>
          <form method="post" data-confirm="Supprimer ce document ?">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $id ?>">
            <input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
            <button type="submit" name="action" value="delete_contract" class="linklike">Supprimer</button>
          </form>
        </div>
      <?php endforeach; ?>
      </div>
      <form method="post" enctype="multipart/form-data" class="card form section upload" data-upload>
        <h3>Ajouter un contrat</h3>
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int) $id ?>">
        <input type="hidden" name="action" value="upload">
        <label>Pour quelle réservation ?
          <select name="request_id">
            <option value="">Aucune en particulier</option>
            <?php foreach ($requests as $r): ?>
            <option value="<?= (int) $r['id'] ?>"<?= selected((int) $r['id'] === $presetResa) ?>><?= h(($r['kind'] === 'journee' ? 'Journée ' : 'Séjour ') . request_label($r) . ' · ' . $r['dog'] . ' · ' . mb_strtolower(STATUS_LABELS[$r['status']] ?? '')) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label>Titre<input type="text" name="title" maxlength="190" placeholder="Ex. : Contrat séjour Max, 12 → 19 déc. 2026"></label>
        <div class="upload__choices">
          <label class="upload__btn upload__btn--main"><?= icon('camera') ?><span>Prendre une photo</span><input type="file" name="photo" accept="image/*" capture="environment" class="sr-only" data-upload-input></label>
          <label class="upload__btn"><?= icon('doc') ?><span>Choisir un fichier (PDF ou photo)</span><input type="file" name="pdf" accept="application/pdf,image/*" class="sr-only" data-upload-input></label>
        </div>
        <p class="upload__name muted small" data-upload-name hidden></p>
        <p class="hint">PDF jusqu'à 10 Mo, ou photo. La photo est redressée et allégée automatiquement. Le client est prévenu par e-mail.</p>
        <button type="submit" class="btn">Déposer le contrat</button>
      </form>
    </section>
    <?php
    page_end(['/js/espace.js']);
    exit;
}

$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
        (SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') FROM dogs d WHERE d.user_id = u.id) AS dogs
    FROM users u WHERE u.role = 'client'";
$params = [];
if ($search !== '') {
    $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, \' \', u.last_name) LIKE ?
        OR EXISTS (SELECT 1 FROM dogs d2 WHERE d2.user_id = u.id AND d2.name LIKE ?))';
    $params = [$like, $like, $like, $like, $like];
}
$clients = q_all($sql . ' ORDER BY u.last_name, u.first_name LIMIT 300', $params);

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
<?php page_end(['/js/espace.js']);
