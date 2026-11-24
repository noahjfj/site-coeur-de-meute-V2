<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; } ?>
    <section class="container admin__login">
      <form class="admin__card" method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="center">
          <span class="admin__lock" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11 V8 a4 4 0 0 1 8 0 V11" /></svg>
          </span>
          <p class="eyebrow">Espace administrateur</p>
          <h1 class="title">Créer votre accès</h1>
        </div>

        <?php if ($setupCode === ''): ?>
        <p class="admin-flash admin-flash--error">Le dossier « admin » n'est pas modifiable par le serveur : impossible de préparer l'installation. Contactez votre hébergeur.</p>
        <?php else: ?>
        <div class="admin-help">
          <p><strong>Pour votre sécurité,</strong> un code d'installation est demandé. Ouvrez le fichier
            <code>admin/code-installation.php</code> avec le gestionnaire de fichiers de votre hébergeur (ou par FTP)&nbsp;: le code y est écrit.</p>
        </div>
        <?php endif; ?>

        <label class="admin__label" for="code">Code d'installation</label>
        <input type="text" id="code" name="code" required maxlength="12" autocapitalize="characters" spellcheck="false" class="admin-code">

        <label class="admin__label" for="user">Identifiant</label>
        <input type="text" id="user" name="user" autocomplete="username" required value="<?= h($oldUser) ?>">

        <label class="admin__label" for="password">Mot de passe (10 caractères minimum)</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required minlength="10">

        <label class="admin__label" for="confirm">Confirmez le mot de passe</label>
        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required minlength="10">

        <p class="muted admin-tip">Astuce : une phrase est plus sûre et plus facile à retenir, par exemple « MaMeuteAdoreLaForet2026 ».</p>

        <button type="submit" name="action" value="setup" class="btn admin__submit">Créer l'accès</button>
      </form>
    </section>
