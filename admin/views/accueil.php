<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }
$tarifs    = load_tarifs();
$photos    = load_photos();
$custom    = count(array_filter($photos['emplacements'], fn($p) => !empty($p['src'])));
$nbSlots   = count(photo_slots());
$checks    = [
    ['Connexion sécurisée (HTTPS)', is_https(), 'Activez le certificat SSL gratuit (souvent appelé « Let\'s Encrypt ») chez votre hébergeur.'],
    ['Dossier des prix modifiable', is_writable(dirname(FILE_TARIFS)), 'Le dossier « data » doit être modifiable par le serveur.'],
    ['Dossier des photos modifiable', is_dir(DIR_UPLOADS) ? is_writable(DIR_UPLOADS) : is_writable(dirname(DIR_UPLOADS)), 'Le dossier « assets/uploads » doit être modifiable par le serveur.'],
    ['Réduction automatique des photos', function_exists('imagecreatetruecolor'), 'Demandez à votre hébergeur d\'activer l\'extension PHP « GD ». Les photos seront sinon enregistrées sans être réduites.'],
];
$fmt = fn(?string $iso) => $iso ? date('d/m/Y à H:i', strtotime($iso)) : 'jamais';
?>
    <section class="container admin__wrap">
      <div class="center">
        <p class="eyebrow">Tableau de bord</p>
        <h1 class="page-hero__title">Bonjour <?= h($access['user']) ?>&nbsp;!</h1>
        <p class="lead narrow-text">Depuis cet espace, vous modifiez les prix de la pension et les photos du site. Les changements sont visibles immédiatement.</p>
      </div>

      <div class="admin-dash">
        <a class="admin-dash__card" href="<?= h(admin_url('tarifs')) ?>">
          <span class="admin-dash__icon admin-dash__icon--rust" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 V21 M16.5 7.5 C16.5 5.6 14.5 4.5 12 4.5 C9.5 4.5 7.5 5.6 7.5 7.7 C7.5 12 16.5 10 16.5 15.8 C16.5 18 14.5 19.5 12 19.5 C9.3 19.5 7.3 18.2 7.3 16" /></svg></span>
          <h2>Tarifs de la pension</h2>
          <ul class="admin-dash__list">
            <?php foreach ($tarifs['gabarits'] as $g): ?>
            <li><?= h($g['nom']) ?> <strong><?= h($g['prix']) ?> €</strong> · <?= h($g['prixLongSejour']) ?> € dès <?= (int) $tarifs['seuilJours'] + 1 ?> j</li>
            <?php endforeach; ?>
          </ul>
          <p class="muted">Dernière modification : <?= h($fmt($tarifs['misAJour'] ?? null)) ?></p>
          <span class="svc-card__more">Modifier les prix</span>
        </a>

        <a class="admin-dash__card" href="<?= h(admin_url('photos')) ?>">
          <span class="admin-dash__icon admin-dash__icon--blue" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="9" cy="10" r="1.8" /><path d="M21 16 L15.5 11 L7 19" /></svg></span>
          <h2>Photos du site</h2>
          <ul class="admin-dash__list">
            <li><strong><?= count($photos['galerie']) ?></strong> photo<?= count($photos['galerie']) > 1 ? 's' : '' ?> dans la galerie</li>
            <li><strong><?= $custom ?></strong> emplacement<?= $custom > 1 ? 's' : '' ?> personnalisé<?= $custom > 1 ? 's' : '' ?> sur <?= $nbSlots ?></li>
          </ul>
          <p class="muted">Dernière modification : <?= h($fmt($photos['misAJour'])) ?></p>
          <span class="svc-card__more">Gérer les photos</span>
        </a>

        <a class="admin-dash__card" href="<?= h(admin_url('compte')) ?>">
          <span class="admin-dash__icon admin-dash__icon--sand" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21 C4 16 7.6 13.5 12 13.5 C16.4 13.5 20 16 20 21" /></svg></span>
          <h2>Mon compte</h2>
          <ul class="admin-dash__list">
            <li>Identifiant : <strong><?= h($access['user']) ?></strong></li>
            <li>Connexion valable <?= SESSION_HOURS ?> heures</li>
          </ul>
          <p class="muted">Pensez à changer votre mot de passe de temps en temps.</p>
          <span class="svc-card__more">Changer le mot de passe</span>
        </a>
      </div>

      <div class="admin__card admin-checks">
        <h2 class="title title--sm">Vérifications de l'hébergement</h2>
        <ul>
          <?php foreach ($checks as [$label, $ok, $help]): ?>
          <li class="<?= $ok ? 'is-ok' : 'is-ko' ?>">
            <span class="admin-checks__dot" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span>
            <div><strong><?= h($label) ?></strong><?php if (!$ok): ?><br><span class="muted"><?= h($help) ?></span><?php endif; ?></div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
