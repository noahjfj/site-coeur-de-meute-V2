<?php
if (!defined('CDM')) { http_response_code(404); exit; }

// Changer ce numéro à chaque mise à jour du style : les navigateurs rechargent alors les fichiers
const ASSET_VERSION = '20261125b';

const CLIENT_NAV = [
    'accueil'      => ['/espace/', 'Mon espace'],
    'demande'      => ['/espace/demande.php', 'Demander un séjour'],
    'reservations' => ['/espace/reservations.php', 'Réservations et contrats'],
    'carte'        => ['/espace/carte.php', 'Mes cartes'],
    'compte'       => ['/espace/compte.php', 'Mon compte'],
];

const GERANTE_NAV = [
    'demandes'   => ['/gerante/', 'Demandes'],
    'historique' => ['/gerante/historique.php', 'Historique'],
    'clients'  => ['/gerante/clients.php', 'Clients et chiens'],
    'cartes'   => ['/gerante/cartes.php', 'Cartes journée'],
    'tarifs'   => ['/gerante/tarifs.php', 'Tarifs'],
];

// Icône de chaque entrée du menu (menu « 3 lignes » sur téléphone)
const NAV_ICONS = [
    'client' => [
        'accueil'      => 'home',
        'demande'      => 'plus',
        'reservations' => 'doc',
        'carte'        => 'card',
        'compte'       => 'user',
    ],
    'gerante' => [
        'demandes'   => 'inbox',
        'historique' => 'clock',
        'clients'    => 'users',
        'cartes'     => 'card',
        'tarifs'     => 'euro',
    ],
];

const ICONS = [
    'home'  => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
    'plus'  => '<rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/>',
    'doc'   => '<path d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5M9.5 13h6M9.5 17h6"/>',
    'card'  => '<rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="M2.5 10h19M6.5 15h4"/>',
    'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4.5-6 8-6s7 2 8 6"/>',
    'inbox' => '<path d="M3 13l3-8h12l3 8v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.5-5.5 6.5-5.5s5.7 2 6.5 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c2 .7 3.2 2.6 3.5 5.2"/>',
    'euro'  => '<path d="M17.5 6.5a7 7 0 1 0 0 11"/><path d="M4 10h9M4 14h9"/>',
    'paw'   => '<circle cx="6" cy="9" r="2"/><circle cx="10" cy="5.5" r="2"/><circle cx="14" cy="5.5" r="2"/><circle cx="18" cy="9" r="2"/><path d="M12 11c-3 0-6 4.5-6 7 0 1.6 1.3 2.5 3 2.5 1.2 0 2-.6 3-.6s1.8.6 3 .6c1.7 0 3-.9 3-2.5 0-2.5-3-7-6-7z"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
];

/** Petite icône au trait (dessin fixe, aucune donnée de l'utilisateur dedans). */
function icon(string $name, string $class = 'ico'): string
{
    return '<svg class="' . h($class) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . (ICONS[$name] ?? '') . '</svg>';
}

/**
 * En-tête commun. $area : 'public' (connexion…), 'client' ou 'gerante'.
 */
function page_start(string $title, string $area = 'public', string $active = ''): void
{
    $user = current_user();
    $nav = $area === 'gerante' ? GERANTE_NAV : ($area === 'client' ? CLIENT_NAV : []);
    ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> — Coeur de Meute</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&amp;family=Open+Sans:wght@400;600;700&amp;display=swap">
<link rel="stylesheet" href="/css/espace.css?v=<?= ASSET_VERSION ?>">
<link rel="icon" href="/assets/img/logo-coeur-de-meute.svg" type="image/svg+xml">
</head>
<body class="area-<?= h($area) ?><?= $nav ? ' has-menu' : '' ?>">
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="topbar<?= $area === 'gerante' ? ' topbar--dark' : '' ?>">
  <div class="wrap topbar__inner">
    <?php if ($nav):
        $pendingCount = $area === 'gerante' ? (int) (q_one("SELECT COUNT(*) AS n FROM stay_requests WHERE status = 'pending'")['n'] ?? 0) : 0; ?>
    <details class="drawer" data-drawer>
      <summary class="drawer__btn" aria-label="Ouvrir le menu"><span class="burger" aria-hidden="true"><i></i><i></i><i></i></span><?php if ($pendingCount > 0): ?><span class="drawer__dot"><?= $pendingCount > 99 ? '99+' : $pendingCount ?></span><?php endif; ?></summary>
      <div class="drawer__panel">
        <div class="drawer__head">
          <span class="drawer__blob" aria-hidden="true"></span>
          <img class="drawer__logo" src="/assets/img/logo-coeur-de-meute.svg" alt="" width="64" height="56">
          <span class="drawer__area"><?= $area === 'gerante' ? 'Espace gérante' : 'Mon espace' ?></span>
          <?php if ($user): ?><strong class="drawer__hello">Bonjour <?= h($user['first_name']) ?></strong><?php endif; ?>
          <button type="button" class="drawer__close" data-drawer-close aria-label="Fermer le menu">×</button>
        </div>
        <nav class="drawer__nav" aria-label="Menu">
          <?php foreach ($nav as $key => [$href, $label]): ?>
          <a class="drawer__link" href="<?= h($href) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= icon(NAV_ICONS[$area][$key] ?? 'arrow') ?><span><?= h($label) ?></span><?php if ($key === 'demandes' && $pendingCount > 0): ?><span class="drawer__count"><?= $pendingCount ?><span class="sr-only"> en attente</span></span><?php endif; ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="drawer__foot">
          <?php if ($area === 'client'): ?><a class="drawer__site" href="/index.html">← Retour au site</a><?php endif; ?>
          <?php if ($user): ?>
          <form method="post" action="/espace/deconnexion.php">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost drawer__logout">Se déconnecter</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </details>
    <?php endif; ?>
    <a class="topbar__logo" href="<?= $area === 'gerante' ? '/gerante/' : '/index.html' ?>"><img src="/assets/img/logo-coeur-de-meute.svg" alt="Coeur de Meute" width="74" height="65"></a>
    <?php if ($area === 'gerante'): ?><span class="topbar__badge">Espace gérante</span><?php endif; ?>
    <?php if ($nav): ?>
    <nav class="topbar__nav" aria-label="Menu de l'espace">
      <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= h($href) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php else: ?>
    <nav class="topbar__nav" aria-label="Retour au site"><a href="/index.html">← Retour au site</a></nav>
    <?php endif; ?>
    <?php if ($user): ?>
    <form class="topbar__user" method="post" action="/espace/deconnexion.php">
      <span class="topbar__name"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></span>
      <?= csrf_field() ?>
      <button type="submit" class="linklike">Déconnexion</button>
    </form>
    <?php endif; ?>
  </div>
</header>
<main id="contenu" class="wrap page">
<?php foreach (take_flashes() as $f): ?>
  <p class="flash flash--<?= h($f['type']) ?>" role="status"><?= h($f['message']) ?></p>
<?php endforeach;
}

function page_end(array $scripts = []): void
{
    if (!in_array('/js/espace.js', $scripts, true)) {
        $scripts[] = '/js/espace.js'; // menu du téléphone, tableaux…
    }
    ?>
</main>
<footer class="wrap foot">
  <p>Coeur de Meute — pension et éducation canine · <a href="/a-propos.html">Contact</a> · <a href="/espace/confidentialite.php">Vos données</a></p>
</footer>
<?php foreach ($scripts as $s): ?>
<script src="<?= h($s) ?>?v=<?= ASSET_VERSION ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

function errors_box(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="flash flash--error" role="alert"><ul>';
    foreach ($errors as $e) {
        echo '<li>' . h($e) . '</li>';
    }
    echo '</ul></div>';
}

function status_badge(string $status): string
{
    return '<span class="badge badge--' . h($status) . '">' . h(STATUS_LABELS[$status] ?? $status) . '</span>';
}

function selected(bool $cond): string
{
    return $cond ? ' selected' : '';
}

function checked(bool $cond): string
{
    return $cond ? ' checked' : '';
}
