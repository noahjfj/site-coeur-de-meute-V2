<?php
/* Conditions générales (séjours, journées, cartes, acompte). Règles réglées par la gérante dans « Infos légales ». */
require __DIR__ . '/../app/bootstrap.php';
$area = current_user() ? (current_user()['role'] === 'gerante' ? 'gerante' : 'client') : 'public';
$l = load_legal();
$pct = load_payment()['pourcentage'];
$pctText = rtrim(rtrim(number_format((float) $pct, 2, ',', ''), '0'), ',');
$tarifs = load_tarifs();
$extra = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $l['regles_accueil']) ?: [])));
page_start('Conditions générales', $area);
?>
<article class="card prose">
  <p class="eyebrow">Conditions générales</p>
  <h1>Conditions générales de la pension</h1>
  <p class="muted">Version du <?= h(legal_date($l['cgv_maj'])) ?>. Elles s'appliquent à chaque demande de séjour, de journée et à chaque achat de carte journée.</p>

  <h2>1. Qui sommes-nous ?</h2>
  <?= legal_identity_html() ?>

  <h2>2. Comment se passe une réservation ?</h2>
  <ol>
    <li>Vous envoyez une <strong>demande</strong> depuis votre espace client. À ce stade, vous ne vous engagez à rien et vous ne payez rien.</li>
    <li>La pension <strong>confirme ou refuse</strong> votre demande, selon les places disponibles et le caractère de votre chien. Vous recevez un e-mail.</li>
    <li>La réservation est <strong>ferme dès la confirmation</strong>.</li>
  </ol>

  <h2>3. Prix</h2>
  <p>Les prix sont ceux affichés sur le site le jour de la demande, en euros, tout compris<?= $l['tva'] === 'assujetti' ? ' (TVA comprise)' : ' (TVA non applicable, régime de la franchise)' ?>. Le prix indiqué lors de la demande est une estimation ; le prix final est confirmé par la pension.</p>

  <h2>4. Acompte pour les séjours</h2>
  <?php if ((float) $pct > 0): ?>
  <p>Après la confirmation d'un séjour, un acompte de <strong><?= h($pctText) ?> % du prix</strong> est demandé, à payer par virement dans les <strong><?= (int) $l['acompte_delai'] ?> jours</strong>. Le reste est payé au plus tard à la fin du séjour (virement ou sur place).</p>
  <p>Sans paiement de l'acompte dans ce délai, la pension peut annuler la réservation après vous avoir prévenu.</p>
  <?php else: ?>
  <p>Aucun acompte n'est demandé pour l'instant. Le séjour est payé au plus tard à la fin du séjour (virement ou sur place).</p>
  <?php endif; ?>
  <p>Les journées payées avec une carte journée ne demandent pas d'acompte.</p>

  <h2>5. Annulation par le client</h2>
  <ul>
    <?php if ((float) $pct > 0): ?>
    <li>Annulation <strong>au moins <?= (int) $l['annulation_jours'] ?> jours avant l'arrivée</strong> : l'acompte est remboursé.</li>
    <li>Annulation <strong>moins de <?= (int) $l['annulation_jours'] ?> jours avant l'arrivée</strong> : l'acompte reste acquis à la pension, qui a bloqué la place pour votre chien.</li>
    <?php endif; ?>
    <li>Une demande pas encore confirmée peut être annulée à tout moment, sans frais, depuis votre espace client.</li>
    <li>Une demande déjà confirmée s'annule en prévenant la pension. Une journée payée avec une carte et annulée est alors remise sur la carte.</li>
  </ul>
  <p>Si la pension doit annuler un séjour confirmé (force majeure, maladie contagieuse…), tout ce que vous avez payé pour ce séjour vous est remboursé.</p>

  <h2>6. Cartes journée</h2>
  <ul>
    <li>Une carte est valable pour les chiens du gabarit choisi, pendant <strong><?= (int) $tarifs['cartes']['validiteMois'] ?> mois</strong> à partir de son activation par la pension.</li>
    <li>Les journées non utilisées à la date de fin ne sont ni remboursées ni prolongées.</li>
    <?php if ((int) $l['carte_rembourse'] === 1): ?>
    <li><strong>Droit de rétractation :</strong> vous pouvez annuler l'achat d'une carte dans les <strong>14 jours</strong> après l'achat, sans donner de raison, en prévenant la pension par e-mail. Si aucune journée n'a été utilisée, tout est remboursé dans les 14 jours. Si vous avez déjà utilisé des journées à votre demande pendant ce délai, elles sont décomptées au prix de la journée.</li>
    <?php endif; ?>
  </ul>

  <h2>7. Accueil de votre chien</h2>
  <ul>
    <li>Votre chien doit avoir ses vaccins à jour et être protégé contre les puces et les tiques. Le carnet de vaccination peut être demandé à l'arrivée.</li>
    <li>Vous indiquez honnêtement dans la fiche de votre chien sa santé, ses traitements et son caractère (peurs, réactions avec les autres chiens, fugues, morsures).</li>
    <li>En cas de maladie ou d'accident pendant le séjour, la pension vous prévient et fait soigner votre chien par son vétérinaire référent ou le vôtre. Les frais vétérinaires sont à votre charge.</li>
    <li>Un contrat d'accueil est signé pour chaque séjour.</li>
    <?php foreach ($extra as $rule): ?><li><?= h($rule) ?></li><?php endforeach; ?>
  </ul>

  <h2>8. Données personnelles</h2>
  <p>Voir la page <a href="/espace/confidentialite.php">Vos données</a>.</p>

  <h2>9. Litiges</h2>
  <p>En cas de désaccord, contactez-nous d'abord. Vous pouvez aussi vous adresser gratuitement au <a href="https://mediationconsommateur.be" rel="noopener">Service de médiation pour le consommateur</a>. Le droit belge s'applique.</p>
</article>
<?php page_end();
