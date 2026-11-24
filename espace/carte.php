<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];

$cards = q_all('SELECT * FROM cards WHERE user_id = ? ORDER BY (expires_at >= CURDATE() AND used_days < paid_days + free_days) DESC, expires_at DESC', [$uid]);
$dogNames = array_column(user_dogs($uid), 'name');
$tarifs = load_tarifs();

page_start('Mes cartes journée', 'client', 'carte');
?>
<p class="eyebrow">Mes cartes</p>
<h1>Mes cartes journée</h1>

<?php if (!$cards): ?>
<div class="card empty">
  <p>Vous n'avez pas encore de carte journée. Avec la carte 10 journées, la 11<sup>e</sup> est offerte ; avec la carte 20, vous recevez 2 journées offertes. Les cartes sont valables <?= (int) $tarifs['cartes']['validiteMois'] ?> mois et servent pour vos chiens du gabarit de la carte (petit, moyen ou grand chien).</p>
  <p>Pour en acheter une, parlez-en à la pension : elle l'activera dans votre espace.</p>
</div>
<?php endif; ?>

<?php if (count($cards) > 1): ?>
<div class="cardtabs" role="tablist" aria-label="Choisir une carte">
  <?php foreach ($cards as $i => $c): $left = card_remaining($c); $off = $c['expires_at'] < today() || $left <= 0; ?>
  <button type="button" class="cardtab cardtab--<?= h($c['size']) ?><?= $off ? ' cardtab--old' : '' ?>" role="tab" id="tab-<?= $i ?>" aria-controls="carte-<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-tab="<?= $i ?>">
    <span class="cardtab__dot" aria-hidden="true"></span>
    <span><strong><?= h(size_label($c['size'])) ?></strong><small>Carte <?= (int) $c['paid_days'] ?> · <?= $off ? ($c['expires_at'] < today() ? 'expirée' : 'terminée') : $left . ' restante' . ($left > 1 ? 's' : '') ?></small></span>
  </button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php foreach ($cards as $i => $c):
    $total = card_total($c);
    $left = card_remaining($c);
    $expired = $c['expires_at'] < today();
    $premium = (int) $c['paid_days'] >= 20;
    $state = $expired ? 'Expirée' : ($left <= 0 ? 'Terminée' : $left . ' restante' . ($left > 1 ? 's' : ''));
?>
<section class="cardshow<?= $expired || $left <= 0 ? ' cardshow--old' : '' ?>" id="carte-<?= $i ?>" data-panel="<?= $i ?>"<?= count($cards) > 1 ? ' role="tabpanel" aria-labelledby="tab-' . $i . '"' : '' ?><?= $i > 0 ? ' hidden' : '' ?>>
  <div class="cardshow__info">
    <dl class="facts facts--tiles">
      <div><dt>Pour</dt><dd><?= h(size_label($c['size'])) ?></dd></div>
      <div><dt>Journées restantes</dt><dd><?= $left ?> / <?= $total ?></dd></div>
      <div><dt>Valable jusqu'au</dt><dd><?= h(date_fr($c['expires_at'])) ?></dd></div>
      <div><dt>Achetée le</dt><dd><?= h(date_fr($c['purchased_at'])) ?></dd></div>
    </dl>
    <?php if (!$expired && $left > 0): ?>
    <a class="btn" href="/espace/demande.php?kind=journee&amp;size=<?= h($c['size']) ?>">Réserver une journée avec cette carte</a>
    <?php endif; ?>
    <p class="muted small">Passez la souris (ou le doigt) sur la carte pour la faire bouger. Cliquez n'importe où dessus pour la retourner et voir vos journées pointées.</p>
  </div>

  <div class="stage" data-card3d>
    <div class="stage__float">
      <div class="stage__tilt" data-tilt>
        <button type="button" class="pcard pcard--<?= h($c['size']) ?><?= $premium ? ' pcard--premium' : '' ?>" data-flip aria-label="Retourner la carte (<?= h($state) ?>)">
          <span class="pcard__face pcard__front">
            <span class="pcard__blob pcard__blob--1" aria-hidden="true"></span>
            <span class="pcard__blob pcard__blob--2" aria-hidden="true"></span>
            <span class="pcard__blob pcard__blob--3" aria-hidden="true"></span>
            <span class="pcard__content">
              <span class="pcard__top">
                <span class="pcard__logo"><img src="/assets/img/logo-coeur-de-meute.svg" alt="" width="64" height="56"></span>
                <span class="pcard__chip"><?= h($state) ?></span>
              </span>
              <span class="pcard__middle">
                <span class="pcard__eyebrow">Carte journée · <?= h(size_label($c['size'])) ?></span>
                <span class="pcard__number"><span class="pcard__big"><?= (int) $c['paid_days'] ?></span><span class="pcard__plus">+ <?= (int) $c['free_days'] ?> offerte<?= $c['free_days'] > 1 ? 's' : '' ?></span></span>
              </span>
              <span class="pcard__bottom">
                <span><span class="pcard__label">Titulaire</span><span class="pcard__value"><?= h($user['first_name'] . ' ' . $user['last_name']) ?><?= $dogNames ? ' · ' . h(implode(' & ', array_slice($dogNames, 0, 3))) : '' ?></span></span>
                <span class="pcard__right"><span class="pcard__label">Valable jusqu'au</span><span class="pcard__value"><?= h((new DateTimeImmutable($c['expires_at']))->format('d/m/Y')) ?></span></span>
              </span>
            </span>
            <span class="pcard__shine" aria-hidden="true"><span class="pcard__sweep"></span><span class="pcard__glare" data-glare></span></span>
          </span>
          <span class="pcard__face pcard__back">
            <span class="pcard__band"><span class="pcard__bandtitle">Mes journées</span><span><?= (int) $c['used_days'] ?> / <?= $total ?> utilisées</span></span>
            <span class="pcard__stamps pcard__stamps--<?= $total > 12 ? 'many' : 'few' ?>">
              <?php for ($i = 0; $i < $total; $i++): $used = $i < (int) $c['used_days']; $free = $i >= (int) $c['paid_days']; ?>
              <span class="stamp<?= $used ? ' stamp--used' : '' ?><?= $free ? ' stamp--free' : '' ?>">
                <span class="stamp__ring"><?php if ($used): ?><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="9" r="2.2"/><circle cx="10" cy="5.5" r="2.2"/><circle cx="14" cy="5.5" r="2.2"/><circle cx="18" cy="9" r="2.2"/><path d="M12 11c-3 0-6 4.5-6 7 0 1.6 1.3 2.5 3 2.5 1.2 0 2-.6 3-.6s1.8.6 3 .6c1.7 0 3-.9 3-2.5 0-2.5-3-7-6-7z"/></svg><?php else: ?><?= $i + 1 ?><?php endif; ?></span>
                <span class="stamp__cap"><?= $free ? 'offerte' : '' ?></span>
              </span>
              <?php endfor; ?>
            </span>
            <span class="pcard__foot">Carte nominative, valable <?= (int) $tarifs['cartes']['validiteMois'] ?> mois après l'achat</span>
          </span>
        </button>
      </div>
    </div>
    <span class="stage__shadow" aria-hidden="true"></span>
  </div>
</section>
<?php endforeach; ?>
<?php page_end(['/js/carte.js']);
