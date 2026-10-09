<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($pages[$view] ?? 'Connexion') ?> — Administration Coeur de Meute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin">

  <header class="site-header admin-header">
    <div class="container site-header__inner">
      <a href="<?= h(admin_url($access && is_logged_in() ? 'accueil' : '')) ?>" class="brand" aria-label="Tableau de bord">
        <span>Coeur</span><span class="logo__de">de</span><span>Meute</span>
        <span class="admin-header__badge">Admin</span>
      </a>
      <div class="admin__header-actions">
        <a href="../index.html" class="btn btn--small btn--outline-dark" target="_blank" rel="noopener">Voir le site</a>
        <?php if (isset($pages[$view])): ?>
        <form method="post">
          <?= csrf_field() ?>
          <button type="submit" name="action" value="logout" class="btn btn--small">Se déconnecter</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php if (isset($pages[$view])): ?>
    <nav class="admin-tabs" aria-label="Sections de l'administration">
      <div class="container admin-tabs__inner">
        <?php foreach ($pages as $key => $label): ?>
        <a href="<?= h(admin_url($key)) ?>"<?= $key === $view ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
        <?php endforeach; ?>
      </div>
    </nav>
    <?php endif; ?>
  </header>

  <main class="section admin-main">
    <?php if ($flashes): ?>
    <div class="container admin__wrap admin-flashes">
      <?php foreach ($flashes as [$type, $message]): ?>
      <p class="admin-flash admin-flash--<?= h($type) ?>" role="status"><?= h($message) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php require __DIR__ . '/' . $view . '.php'; ?>
  </main>

</body>
</html>
