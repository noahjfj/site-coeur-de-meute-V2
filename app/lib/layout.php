<?php
if (!defined('CDM')) { http_response_code(404); exit; }

// Changer ce numéro à chaque mise à jour du style : les navigateurs rechargent alors les fichiers
const ASSET_VERSION = '20261124b';

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
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> — Coeur de Meute</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&amp;family=Open+Sans:wght@400;600;700&amp;display=swap">
<link rel="stylesheet" href="/css/espace.css?v=<?= ASSET_VERSION ?>">
<link rel="icon" href="/assets/img/logo-coeur-de-meute.svg" type="image/svg+xml">
</head>
<body class="area-<?= h($area) ?>">
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="topbar<?= $area === 'gerante' ? ' topbar--dark' : '' ?>">
  <div class="wrap topbar__inner">
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
      <span><?= h($user['first_name'] . ' ' . $user['last_name']) ?></span>
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
