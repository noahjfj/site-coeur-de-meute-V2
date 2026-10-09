<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }
$photos  = load_photos();
$maxMb   = MAX_UPLOAD_MB;
$accept  = 'image/jpeg,image/png,image/webp';
?>
    <section class="container admin__wrap admin__wrap--wide">
      <div class="center">
        <p class="eyebrow">Photos</p>
        <h1 class="page-hero__title">Les photos du site</h1>
        <p class="lead narrow-text">Remplacez les photos de chaque page et gérez votre galerie. Formats acceptés : JPG, PNG ou WEBP, <?= $maxMb ?> Mo maximum. Les grandes photos sont réduites automatiquement.</p>
      </div>

      <nav class="admin-anchors" aria-label="Aller à">
        <?php foreach (PHOTO_GROUPS as $gid => $group): ?>
        <a href="#groupe-<?= h($gid) ?>"><?= h($group['titre']) ?></a>
        <?php endforeach; ?>
        <a href="#galerie">Galerie</a>
      </nav>

      <?php foreach (PHOTO_GROUPS as $gid => $group): ?>
      <div class="admin__card" id="groupe-<?= h($gid) ?>">
        <h2 class="title title--sm"><?= h($group['titre']) ?></h2>
        <div class="slot-grid">
          <?php foreach ($group['emplacements'] as $key => $slot):
            $current = slot_current($photos, $key);
            $custom  = !empty($photos['emplacements'][$key]['src']);
            ?>
          <article class="slot" id="photo-<?= h($key) ?>">
            <div class="slot__preview<?= $current ? '' : ' slot__preview--empty' ?>">
              <?php if ($current): ?>
              <img src="../<?= h($current['src']) ?>" alt="<?= h($current['alt'] ?? '') ?>">
              <?php else: ?>
              <span>Pas de photo&nbsp;: un dessin s'affiche à la place</span>
              <?php endif; ?>
              <span class="slot__badge slot__badge--<?= $custom ? 'custom' : 'origin' ?>"><?= $custom ? 'Votre photo' : ($current ? "Photo d'origine" : 'Dessin') ?></span>
            </div>
            <div class="slot__body">
              <h3><?= h($slot['label']) ?></h3>
              <p class="muted">Page : <a href="../<?= h($slot['page']) ?>" target="_blank" rel="noopener"><?= h($slot['page']) ?></a> · Format conseillé : <?= h($slot['format']) ?></p>

              <form method="post" enctype="multipart/form-data" class="slot__form">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="photos">
                <input type="hidden" name="slot" value="<?= h($key) ?>">
                <label class="admin-file">
                  <input type="file" name="photo" accept="<?= h($accept) ?>">
                  <span>Choisir une photo…</span>
                </label>
                <label class="admin__label" for="alt-<?= h($key) ?>">Description (pour Google et les malvoyants)</label>
                <input type="text" id="alt-<?= h($key) ?>" name="alt" maxlength="150" value="<?= h($photos['emplacements'][$key]['alt'] ?? $slot['alt']) ?>">
                <div class="slot__actions">
                  <button type="submit" name="action" value="slot_save" class="btn btn--small">Enregistrer</button>
                  <?php if ($custom): ?>
                  <button type="submit" name="action" value="slot_reset" class="btn btn--small btn--outline-dark" formnovalidate
                          onclick="return confirm(<?= h(json_encode($slot['defaut'] ? "Remettre la photo d'origine ?" : 'Retirer cette photo ?', JSON_UNESCAPED_UNICODE)) ?>);">
                    <?= $slot['defaut'] ? "Remettre l'originale" : 'Retirer' ?>
                  </button>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- ===== GALERIE ===== -->
      <div class="admin__card" id="galerie">
        <h2 class="title title--sm">Galerie (<?= count($photos['galerie']) ?> photo<?= count($photos['galerie']) > 1 ? 's' : '' ?>)</h2>
        <p class="muted">Les photos s'affichent sur la page <a href="../galerie.html" target="_blank" rel="noopener">Galerie</a> dans l'ordre ci-dessous. La première est affichée en grand.</p>

        <form method="post" enctype="multipart/form-data" class="gallery-add">
          <?= csrf_field() ?>
          <input type="hidden" name="return" value="photos">
          <label class="admin-file admin-file--big">
            <input type="file" name="photos[]" accept="<?= h($accept) ?>" multiple>
            <span>＋ Choisir une ou plusieurs photos…</span>
          </label>
          <button type="submit" name="action" value="gallery_add" class="btn">Ajouter à la galerie</button>
        </form>

        <?php if ($photos['galerie']): ?>
        <form method="post" id="gallery-captions">
          <?= csrf_field() ?>
          <input type="hidden" name="return" value="photos">
        </form>

        <ol class="gallery-admin">
          <?php foreach ($photos['galerie'] as $i => $item): $last = $i === count($photos['galerie']) - 1; ?>
          <li class="gallery-admin__item">
            <div class="gallery-admin__img"><img src="../<?= h($item['src']) ?>" alt=""><span><?= $i + 1 ?></span></div>
            <label class="admin__label" for="legende-<?= $i ?>">Légende</label>
            <input type="text" id="legende-<?= $i ?>" name="legende[<?= $i ?>]" maxlength="120" value="<?= h($item['legende'] ?? '') ?>" form="gallery-captions" placeholder="Ex. : Balade au bord de la Meuse">
            <div class="gallery-admin__tools">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="photos">
                <input type="hidden" name="index" value="<?= $i ?>">
                <button type="submit" name="action" value="gallery_move" class="icon-btn" title="Monter" aria-label="Déplacer avant" <?= $i === 0 ? 'disabled' : '' ?> onclick="this.form.direction.value='up'">↑</button>
                <button type="submit" name="action" value="gallery_move" class="icon-btn" title="Descendre" aria-label="Déplacer après" <?= $last ? 'disabled' : '' ?> onclick="this.form.direction.value='down'">↓</button>
                <input type="hidden" name="direction" value="down">
              </form>
              <form method="post" onsubmit="return confirm('Retirer cette photo de la galerie ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="photos">
                <input type="hidden" name="index" value="<?= $i ?>">
                <button type="submit" name="action" value="gallery_delete" class="icon-btn icon-btn--danger" title="Supprimer" aria-label="Supprimer la photo">✕</button>
              </form>
            </div>
          </li>
          <?php endforeach; ?>
        </ol>

        <div class="admin__actions">
          <button type="submit" name="action" value="gallery_update" class="btn" form="gallery-captions">Enregistrer les légendes</button>
        </div>
        <?php else: ?>
        <p class="muted">La galerie est vide pour le moment&nbsp;: des cases « Photo à venir » s'affichent sur le site.</p>
        <?php endif; ?>
      </div>
    </section>

    <script>
      // Affiche le nom des photos choisies
      document.querySelectorAll('.admin-file input').forEach(function (input) {
        input.addEventListener('change', function () {
          var label = input.nextElementSibling;
          var n = input.files.length;
          label.textContent = n === 0 ? label.dataset.empty : (n === 1 ? input.files[0].name : n + ' photos choisies');
          input.closest('.admin-file').classList.toggle('has-file', n > 0);
        });
        input.nextElementSibling.dataset.empty = input.nextElementSibling.textContent;
      });
    </script>
