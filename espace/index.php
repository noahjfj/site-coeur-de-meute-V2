<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];

$dogs = user_dogs($uid);
$next = q_one("SELECT r.*, d.name AS dog FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.user_id = ? AND r.status = 'confirmed' AND r.end_date >= ? ORDER BY r.start_date LIMIT 1", [$uid, today()]);
$pending = q_all("SELECT r.*, d.name AS dog FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.user_id = ? AND r.status = 'pending' ORDER BY r.start_date", [$uid]);
$cards = active_cards($uid);
$remaining = remaining_days($uid);

page_start('Mon espace', 'client', 'accueil');
?>
<p class="eyebrow">Mon espace</p>
<h1>Bonjour <?= h($user['first_name']) ?></h1>
<nav class="quick" aria-label="Raccourcis">
  <a class="quick__item quick__item--main" href="/espace/demande.php"><?= icon('plus') ?><span>Demander un séjour</span></a>
  <a class="quick__item" href="/espace/demande.php?kind=journee"><?= icon('card') ?><span>Réserver une journée</span></a>
  <a class="quick__item" href="/espace/reservations.php"><?= icon('doc') ?><span>Mes réservations</span></a>
  <a class="quick__item" href="/espace/chien.php"><?= icon('paw') ?><span>Ajouter un chien</span></a>
</nav>

<div class="tiles">
  <div class="tile">
    <span class="tile__label">Prochain séjour</span>
    <?php if ($next): ?>
      <strong class="tile__value"><?= h(request_label($next)) ?></strong>
      <span class="tile__note tile__note--ok"><?= h($next['dog']) ?> · confirmé</span>
    <?php else: ?>
      <strong class="tile__value">Aucun pour l'instant</strong>
      <a class="tile__note" href="/espace/demande.php">Demander un séjour →</a>
    <?php endif; ?>
  </div>
  <div class="tile">
    <span class="tile__label">Demandes en attente</span>
    <strong class="tile__value"><?= count($pending) ?> demande<?= count($pending) > 1 ? 's' : '' ?></strong>
    <?php if ($pending): ?><span class="tile__note tile__note--wait"><?= h($pending[0]['dog'] . ' · ' . request_label($pending[0])) ?></span><?php endif; ?>
  </div>
  <a class="tile tile--dark" href="/espace/carte.php">
    <span class="tile__label">Carte journée</span>
    <?php if ($cards): ?>
      <strong class="tile__value"><?= $remaining ?> journée<?= $remaining > 1 ? 's' : '' ?> restante<?= $remaining > 1 ? 's' : '' ?></strong>
      <span class="tile__note">Valable jusqu'au <?= h(date_fr($cards[0]['expires_at'])) ?> →</span>
    <?php else: ?>
      <strong class="tile__value">Pas de carte active</strong>
      <span class="tile__note">En savoir plus →</span>
    <?php endif; ?>
  </a>
</div>

<section class="section">
  <div class="section__head">
    <h2>Mes chiens</h2>
    <a class="btn btn--small" href="/espace/chien.php">+ Ajouter un chien</a>
  </div>
  <?php if (!$dogs): ?>
    <div class="card empty">
      <p>Vous n'avez pas encore enregistré de chien. Commencez par sa fiche : elle aide la pension à bien s'en occuper.</p>
      <a class="btn" href="/espace/chien.php">Ajouter mon premier chien</a>
    </div>
  <?php else: ?>
  <div class="dogs">
    <?php foreach ($dogs as $i => $d): ?>
    <article class="card dog">
      <div class="dog__head">
        <?php if ($photo = dog_photo_url($d)): ?>
        <a class="dog__avatar dog__avatar--photo" href="/espace/chien.php?id=<?= (int) $d['id'] ?>"><img src="<?= h($photo) ?>" alt="Photo de <?= h($d['name']) ?>" width="72" height="72" loading="lazy"></a>
        <?php else: ?>
        <a class="dog__avatar dog__avatar--<?= $i % 3 ?> dog__avatar--add" href="/espace/chien.php?id=<?= (int) $d['id'] ?>" title="Ajouter une photo"><span class="sr-only">Ajouter une photo de <?= h($d['name']) ?></span><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="6" cy="9" r="2.2"/><circle cx="10" cy="5.5" r="2.2"/><circle cx="14" cy="5.5" r="2.2"/><circle cx="18" cy="9" r="2.2"/><path d="M12 11c-3 0-6 4.5-6 7 0 1.6 1.3 2.5 3 2.5 1.2 0 2-.6 3-.6s1.8.6 3 .6c1.7 0 3-.9 3-2.5 0-2.5-3-7-6-7z"/></svg><span class="dog__plus" aria-hidden="true">+</span></a>
        <?php endif; ?>
        <div class="dog__title">
          <h3><?= h($d['name']) ?></h3>
          <span><?= h(implode(' · ', array_filter([$d['breed'], dog_age($d['birth_date']), $d['sex'] === 'male' ? 'mâle' : ($d['sex'] === 'femelle' ? 'femelle' : '')]))) ?></span>
        </div>
        <span class="pill"><?= h(size_label($d['size'])) ?></span>
      </div>
      <dl class="facts">
        <div><dt>Vaccins</dt><dd class="<?= $d['vaccines_until'] && $d['vaccines_until'] >= today() ? 'ok' : 'warn' ?>"><?= $d['vaccines_until'] ? ($d['vaccines_until'] >= today() ? 'À jour (' . h(date_fr($d['vaccines_until'])) . ')' : 'À renouveler') : 'Non renseigné' ?></dd></div>
        <div><dt>Vétérinaire</dt><dd><?= h($d['vet'] ?: '—') ?></dd></div>
        <div><dt>Santé</dt><dd><?= h($d['health'] ?: 'Rien à signaler') ?></dd></div>
        <div><dt>Caractère</dt><dd><?= h($d['temperament'] ?: '—') ?></dd></div>
      </dl>
      <a href="/espace/chien.php?id=<?= (int) $d['id'] ?>">Voir ou modifier la fiche</a>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php page_end();
