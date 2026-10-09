<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; } ?>
    <section class="container admin__wrap">
      <div class="center">
        <p class="eyebrow">Mon compte</p>
        <h1 class="page-hero__title">Sécurité de votre accès</h1>
      </div>

      <form class="admin__card admin-form-narrow" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="compte">
        <h2 class="title title--sm">Changer mon mot de passe</h2>
        <p class="muted">Identifiant : <strong><?= h($access['user']) ?></strong></p>

        <label class="admin__label" for="current">Mot de passe actuel</label>
        <input type="password" id="current" name="current" autocomplete="current-password" required>
        <label class="admin__label" for="new">Nouveau mot de passe (10 caractères minimum)</label>
        <input type="password" id="new" name="new" autocomplete="new-password" required minlength="10">
        <label class="admin__label" for="confirm-new">Confirmez le nouveau mot de passe</label>
        <input type="password" id="confirm-new" name="confirm" autocomplete="new-password" required minlength="10">

        <div class="admin__actions">
          <button type="submit" name="action" value="password" class="btn">Changer le mot de passe</button>
        </div>
      </form>

      <div class="admin__card admin-form-narrow">
        <h2 class="title title--sm">Comment votre accès est protégé</h2>
        <ul class="checklist">
          <li>Mot de passe enregistré chiffré, jamais en clair</li>
          <li>Blocage de <?= LOCK_MINUTES ?> minutes après <?= MAX_ATTEMPTS ?> mots de passe erronés</li>
          <li>Connexion automatiquement fermée après <?= SESSION_HOURS ?> heures</li>
          <li>Protection contre les formulaires piégés venant d'autres sites</li>
        </ul>
        <p class="muted">Mot de passe oublié&nbsp;? Supprimez le fichier <code>admin/acces.php</code> sur votre hébergement&nbsp;: un nouveau code d'installation sera créé pour recréer votre accès.</p>
      </div>
    </section>
