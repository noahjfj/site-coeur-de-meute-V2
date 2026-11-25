<?php
/* Infos légales de la pension + règles des conditions générales (modifiables par la gérante). */
require __DIR__ . '/../app/bootstrap.php';
$me = require_gerante();
$l = load_legal();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $l;
    foreach (['nom_commercial' => 120, 'nom_legal' => 120, 'forme' => 60, 'adresse' => 255, 'telephone' => 40, 'agrement' => 60] as $k => $max) {
        $l[$k] = post($k, $max);
    }
    if ($l['nom_commercial'] === '') {
        $errors[] = 'Le nom de la pension est obligatoire.';
    }
    $email = post('email', 190);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse e-mail n\'est pas valide.';
    } else {
        $l['email'] = $email;
    }
    $bce = post('bce', 20);
    if ($bce === '') {
        $l['bce'] = '';
    } elseif ($clean = clean_bce($bce)) {
        $l['bce'] = $clean;
    } else {
        $errors[] = 'Ce numéro d\'entreprise (BCE) n\'est pas valide. Il a 10 chiffres, par exemple 0123.456.749.';
    }
    $l['tva'] = post('tva', 12) === 'assujetti' ? 'assujetti' : 'franchise';
    foreach (['acompte_delai' => [1, 60], 'annulation_jours' => [0, 180]] as $k => [$min, $max]) {
        $raw = post($k, 3);
        if (!ctype_digit($raw) || (int) $raw < $min || (int) $raw > $max) {
            $errors[] = 'Nombre de jours invalide (entre ' . $min . ' et ' . $max . ').';
        } else {
            $l[$k] = (int) $raw;
        }
    }
    $l['carte_rembourse'] = post('carte_rembourse', 3) === '1' ? 1 : 0;
    $l['regles_accueil'] = post('regles_accueil', 4000);

    // Les règles ont changé : nouvelle version des conditions (les clients les acceptent à nouveau à leur prochaine demande)
    foreach (['acompte_delai', 'annulation_jours', 'carte_rembourse', 'regles_accueil'] as $k) {
        if ((string) $old[$k] !== (string) $l[$k]) {
            $l['cgv_maj'] = date('Y-m-d');
        }
    }
    if (!$errors) {
        if (save_legal($l)) {
            audit((int) $me['id'], 'legal_settings_updated');
            flash('success', 'Les infos sont enregistrées. Les pages Mentions légales, Conditions générales et Vos données sont à jour.');
            redirect('/gerante/entreprise.php');
        }
        $errors[] = 'Impossible d\'enregistrer : le dossier app/storage n\'est pas modifiable sur l\'hébergement.';
    }
}

$missing = legal_missing();
page_start('Infos légales', 'gerante', 'tarifs');
?>
<p class="eyebrow"><a href="/gerante/tarifs.php">Tarifs</a></p>
<form method="post" class="card section form form--wide">
  <h1>Infos légales et conditions</h1>
  <p class="muted">Ces infos s'affichent dans les pages <a href="/espace/mentions-legales.php" target="_blank" rel="noopener">Mentions légales</a>, <a href="/espace/conditions.php" target="_blank" rel="noopener">Conditions générales</a> et <a href="/espace/confidentialite.php" target="_blank" rel="noopener">Vos données</a>. La loi demande qu'elles soient sur le site.</p>
  <?php if ($missing && !$errors): ?><p class="flash flash--info">Encore à compléter : <?= h(implode(', ', $missing)) ?>.</p><?php endif; ?>
  <?php errors_box($errors); ?>
  <?= csrf_field() ?>

  <h2>L'entreprise</h2>
  <div class="grid2">
    <label>Nom de la pension<input type="text" name="nom_commercial" value="<?= h($l['nom_commercial']) ?>" required maxlength="120"></label>
    <label>Nom légal <span class="hint">(votre nom, ou celui de la société)</span><input type="text" name="nom_legal" value="<?= h($l['nom_legal']) ?>" maxlength="120"></label>
    <label>Forme <span class="hint">(ex. : indépendante, SRL)</span><input type="text" name="forme" value="<?= h($l['forme']) ?>" maxlength="60"></label>
    <label>Numéro d'entreprise (BCE)<input type="text" name="bce" value="<?= h($l['bce']) ?>" placeholder="0123.456.789" inputmode="numeric" maxlength="20"></label>
    <label>Adresse<input type="text" name="adresse" value="<?= h($l['adresse']) ?>" maxlength="255"></label>
    <label>E-mail de contact<input type="email" name="email" value="<?= h($l['email']) ?>" maxlength="190"></label>
    <label>Téléphone<input type="text" name="telephone" value="<?= h($l['telephone']) ?>" maxlength="40"></label>
    <label>Numéro d'agrément de la pension<input type="text" name="agrement" value="<?= h($l['agrement']) ?>" maxlength="60" placeholder="donné par la Région wallonne"></label>
  </div>
  <fieldset class="choices">
    <legend>TVA</legend>
    <label class="choice"><input type="radio" name="tva" value="franchise"<?= checked($l['tva'] === 'franchise') ?>><span><strong>Pas de TVA</strong><small>Régime de la franchise (petite entreprise)</small></span></label>
    <label class="choice"><input type="radio" name="tva" value="assujetti"<?= checked($l['tva'] === 'assujetti') ?>><span><strong>Assujettie à la TVA</strong><small>Le numéro de TVA est BE + le numéro BCE ; les prix affichés sont TVA comprise</small></span></label>
  </fieldset>

  <h2 id="conditions">Règles des conditions générales</h2>
  <p class="muted">Les clients acceptent ces règles en cochant une case avant chaque demande de séjour et chaque achat de carte.</p>
  <div class="grid2">
    <label>Délai pour payer l'acompte<span class="money"><input type="text" inputmode="numeric" name="acompte_delai" value="<?= (int) $l['acompte_delai'] ?>" maxlength="3"> jours après la confirmation</span></label>
    <label>Acompte remboursé si annulation au moins<span class="money"><input type="text" inputmode="numeric" name="annulation_jours" value="<?= (int) $l['annulation_jours'] ?>" maxlength="3"> jours avant l'arrivée</span></label>
  </div>
  <label class="check"><input type="checkbox" name="carte_rembourse" value="1"<?= checked((int) $l['carte_rembourse'] === 1) ?>><span>Une carte journée pas encore utilisée est remboursée si le client l'annule dans les 14 jours après l'achat <span class="hint">(c'est la loi pour un achat en ligne : laissez cochée)</span></span></label>
  <label>Règles en plus <span class="hint">(facultatif : conditions d'accueil, retard, maladie… une règle par ligne)</span><textarea name="regles_accueil" rows="5" maxlength="4000"><?= h($l['regles_accueil']) ?></textarea></label>
  <p class="muted small">Dernière modification des règles : <?= h(date('d/m/Y', strtotime($l['cgv_maj']))) ?>.</p>
  <button type="submit" class="btn">Enregistrer</button>
</form>
<?php page_end();
