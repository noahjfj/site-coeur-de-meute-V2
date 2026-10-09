<?php if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }
$tarifs = load_tarifs();
?>
    <section class="container admin__wrap">
      <div class="center">
        <p class="eyebrow">Tarifs</p>
        <h1 class="page-hero__title">Prix de la pension canine</h1>
        <p class="lead narrow-text">Ces prix alimentent le simulateur de l'accueil et de la page Pension, ainsi que le « À partir de … € ».</p>
      </div>

      <form class="admin__card" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="tarifs">

        <h2 class="title title--sm">Règle du tarif long séjour</h2>
        <label class="admin__inline">
          Le tarif long séjour s'applique quand le séjour dépasse
          <input type="number" name="seuilJours" min="1" max="60" step="1" required value="<?= h($tarifs['seuilJours']) ?>">
          jours.
        </label>

        <h2 class="title title--sm">Prix par jour</h2>
        <div class="admin__table-wrap">
          <table class="admin__table">
            <thead>
              <tr>
                <th scope="col">Gabarit</th>
                <th scope="col">Description</th>
                <th scope="col">Prix / jour</th>
                <th scope="col">Prix long séjour / jour</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tarifs['gabarits'] as $i => $g): ?>
              <tr>
                <td>
                  <input type="hidden" name="id[<?= $i ?>]" value="<?= h($g['id']) ?>">
                  <input type="text" name="nom[<?= $i ?>]" maxlength="40" required value="<?= h($g['nom']) ?>" aria-label="Nom du gabarit <?= $i + 1 ?>">
                </td>
                <td><input type="text" name="description[<?= $i ?>]" maxlength="60" value="<?= h($g['description']) ?>" aria-label="Description du gabarit <?= $i + 1 ?>"></td>
                <td><span class="admin__money"><input type="number" name="prix[<?= $i ?>]" min="0.5" max="1000" step="0.5" required value="<?= h($g['prix']) ?>" aria-label="Prix par jour, <?= h($g['nom']) ?>"> €</span></td>
                <td><span class="admin__money"><input type="number" name="prixLongSejour[<?= $i ?>]" min="0.5" max="1000" step="0.5" required value="<?= h($g['prixLongSejour']) ?>" aria-label="Prix long séjour par jour, <?= h($g['nom']) ?>"> €</span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="admin__actions">
          <button type="submit" name="action" value="save_tarifs" class="btn">Enregistrer les nouveaux prix</button>
          <a href="<?= h(admin_url('tarifs')) ?>" class="btn btn--outline-dark">Annuler les modifications</a>
        </div>
        <p class="muted">
          <?= !empty($tarifs['misAJour']) ? 'Dernière modification : ' . h(date('d/m/Y à H:i', strtotime($tarifs['misAJour']))) : 'Prix par défaut (aucune modification enregistrée pour le moment).' ?>
        </p>
      </form>
    </section>
