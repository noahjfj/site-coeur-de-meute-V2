<?php
/* Planning du mois : qui est à la pension chaque jour (séjours et journées). */
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action', 20) === 'new_link') {
    agenda_token(true);
    audit((int) $me['id'], 'agenda_link_renewed');
    flash('success', 'Nouveau lien créé. L\'ancien ne marche plus : ajoutez le nouveau dans Google Agenda.');
    redirect('/gerante/planning.php#agenda');
}

$monthParam = is_string($_GET['mois'] ?? null) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['mois']) ? $_GET['mois'] : date('Y-m');
$first = new DateTimeImmutable($monthParam . '-01');
$last = $first->modify('last day of this month');
$rows = planning_rows($first->format('Y-m-d'), $last->format('Y-m-d'));

// Qui est présent chaque jour (le jour de départ compte)
$days = [];
foreach ($rows as $r) {
    $d = max($r['start_date'], $first->format('Y-m-d'));
    $end = min($r['end_date'], $last->format('Y-m-d'));
    while ($d <= $end) {
        $days[$d][] = $r;
        $d = (new DateTimeImmutable($d))->modify('+1 day')->format('Y-m-d');
    }
}
$moisFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$joursFr = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
$title = ucfirst($moisFr[(int) $first->format('n') - 1]) . ' ' . $first->format('Y');
$busiest = $days ? max(array_map('count', $days)) : 0;

function chip(array $r, string $day): string
{
    $tag = $r['kind'] === 'journee' ? 'journée' : ($r['start_date'] === $day ? 'arrivée' : ($r['end_date'] === $day ? 'départ' : ''));
    return '<a class="pchip pchip--' . h($r['size']) . ($r['status'] === 'pending' ? ' pchip--pending' : '') . '" href="/gerante/clients.php?id=' . (int) $r['uid'] . '" title="'
        . h($r['dog'] . ' · ' . $r['first_name'] . ' ' . $r['last_name'] . ($r['status'] === 'pending' ? ' (en attente)' : '')) . '">'
        . h($r['dog']) . ($tag ? ' <small>' . h($tag) . '</small>' : '') . '</a>';
}

page_start('Planning', 'gerante', 'planning');
?>
<section class="card section">
  <div class="section__head planning__head">
    <h1>Planning</h1>
    <nav class="monthnav" aria-label="Changer de mois">
      <a class="btn btn--ghost btn--small" href="?mois=<?= $first->modify('-1 month')->format('Y-m') ?>" aria-label="Mois précédent">‹</a>
      <strong><?= h($title) ?></strong>
      <a class="btn btn--ghost btn--small" href="?mois=<?= $first->modify('+1 month')->format('Y-m') ?>" aria-label="Mois suivant">›</a>
      <?php if ($monthParam !== date('Y-m')): ?><a class="linklike" href="/gerante/planning.php">Aujourd'hui</a><?php endif; ?>
    </nav>
  </div>
  <p class="muted small"><span class="legend legend--confirmed"></span> confirmé · <span class="legend legend--pending"></span> en attente · couleur = gabarit (<span class="dotsize dotsize--petit"></span> petit, <span class="dotsize dotsize--moyen"></span> moyen, <span class="dotsize dotsize--grand"></span> grand)<?= $busiest ? ' · jusqu\'à ' . $busiest . ' chien' . ($busiest > 1 ? 's' : '') . ' le même jour' : '' ?></p>

  <div class="cal" role="table" aria-label="Planning de <?= h($title) ?>">
    <div class="cal__row cal__row--head" role="row">
      <?php foreach ($joursFr as $j): ?><span role="columnheader"><?= h(mb_substr($j, 0, 3)) ?></span><?php endforeach; ?>
    </div>
    <div class="cal__grid">
      <?php for ($i = 1; $i < (int) $first->format('N'); $i++): ?><span class="cal__cell cal__cell--empty"></span><?php endfor; ?>
      <?php for ($d = $first; $d <= $last; $d = $d->modify('+1 day')):
          $iso = $d->format('Y-m-d');
          $list = $days[$iso] ?? [];
          $n = count($list); ?>
      <div class="cal__cell<?= $iso === today() ? ' is-today' : '' ?><?= $n ? ' has-dogs' : '' ?>">
        <span class="cal__day"><?= (int) $d->format('j') ?><?php if ($n): ?><span class="cal__count"><?= $n ?></span><?php endif; ?></span>
        <?php foreach (array_slice($list, 0, 4) as $r): ?><?= chip($r, $iso) ?><?php endforeach; ?>
        <?php if ($n > 4): ?><span class="cal__more">+ <?= $n - 4 ?></span><?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="agendalist">
    <?php if (!$days): ?><p class="muted">Aucun chien prévu ce mois-ci.</p><?php endif; ?>
    <?php foreach ($days as $iso => $list): $d = new DateTimeImmutable($iso); ?>
    <div class="agendalist__day<?= $iso === today() ? ' is-today' : '' ?>">
      <div class="agendalist__date"><span><?= h(mb_substr($joursFr[(int) $d->format('N') - 1], 0, 3)) ?></span><strong><?= (int) $d->format('j') ?></strong></div>
      <div class="agendalist__dogs">
        <span class="agendalist__n"><?= count($list) ?> chien<?= count($list) > 1 ? 's' : '' ?></span>
        <?php foreach ($list as $r): ?><?= chip($r, $iso) ?><?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="card section" id="agenda">
  <h2>Dans Google Agenda</h2>
  <p>Les séjours et journées <strong>confirmés</strong> peuvent apparaître tout seuls dans votre Google Agenda. Vous l'ajoutez une seule fois :</p>
  <ol class="steps">
    <li>Copiez ce lien : <span class="copybox"><input type="text" readonly value="<?= h(agenda_url()) ?>" aria-label="Lien de l'agenda" data-copy-src><button type="button" class="btn btn--small" data-copy>Copier</button></span></li>
    <li>Sur un ordinateur, ouvrez <strong>Google Agenda</strong>. À gauche, à côté de « Autres agendas », cliquez sur <strong>+</strong>, puis sur <strong>« À partir de l'URL »</strong>.</li>
    <li>Collez le lien et cliquez sur <strong>« Ajouter un agenda »</strong>. C'est tout : l'agenda « Coeur de Meute – pension » apparaît aussi sur votre téléphone.</li>
  </ol>
  <p class="muted small">Google met à jour ce type d'agenda tout seul, mais avec un peu de retard (souvent quelques heures). Ce lien est personnel : ne le partagez pas. En cas de doute, créez-en un nouveau (l'ancien cessera de marcher).</p>
  <form method="post" data-confirm="Créer un nouveau lien ? L'ancien ne marchera plus et il faudra ajouter le nouveau dans Google Agenda.">
    <?= csrf_field() ?>
    <button type="submit" name="action" value="new_link" class="linklike">Créer un nouveau lien</button>
  </form>
</section>
<?php page_end(['/js/espace.js']);
