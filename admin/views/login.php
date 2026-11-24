<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; } ?>
<?php $wait = lock_remaining(); ?>
    <section class="container admin__login">
      <form class="admin__card" method="post">
        <?= csrf_field() ?>
        <div class="center">
          <span class="admin__lock" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11 V8 a4 4 0 0 1 8 0 V11" /></svg>
          </span>
          <p class="eyebrow">Espace administrateur</p>
          <h1 class="title">Connexion</h1>
        </div>

        <?php if ($wait > 0): ?>
        <p class="admin-flash admin-flash--error">Trop de tentatives. Pour votre sécurité, la connexion est bloquée encore <?= (int) ceil($wait / 60) ?> minute(s).</p>
        <?php else: ?>
        <label class="admin__label" for="user">Identifiant</label>
        <input type="text" id="user" name="user" autocomplete="username" required value="<?= h($oldUser) ?>">

        <label class="admin__label" for="password">Mot de passe</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit" name="action" value="login" class="btn admin__submit">Se connecter</button>
        <?php endif; ?>
      </form>
      <p class="muted center admin-forgot">Mot de passe oublié&nbsp;? Supprimez le fichier <code>admin/acces.php</code> sur votre hébergement, puis revenez sur cette page pour créer un nouvel accès.</p>
    </section>
