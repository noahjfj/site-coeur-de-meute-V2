<?php
/* Mentions légales (Code de droit économique, art. XII.6). Les infos viennent de la page « Infos légales » de la gérante. */
require __DIR__ . '/../app/bootstrap.php';
$area = current_user() ? (current_user()['role'] === 'gerante' ? 'gerante' : 'client') : 'public';
page_start('Mentions légales', $area);
?>
<article class="card prose">
  <p class="eyebrow">Informations légales</p>
  <h1>Mentions légales</h1>
  <h2>Qui édite ce site ?</h2>
  <?= legal_identity_html() ?>
  <h2>Hébergement</h2>
  <p>Le site est hébergé par Hostinger International Ltd, 61 Lordou Vironos Street, 6023 Larnaca, Chypre (<a href="https://www.hostinger.fr" rel="noopener">hostinger.fr</a>), sur des serveurs situés dans l'Union européenne.</p>
  <h2>Prix et réservations</h2>
  <p>Les prix affichés sur le site sont des prix tout compris, en euros. Les réservations et l'achat de cartes journée sont régis par nos <a href="/espace/conditions.php">conditions générales</a>.</p>
  <h2>Vos données et les cookies</h2>
  <p>Voir la page <a href="/espace/confidentialite.php">Vos données</a>.</p>
  <h2>Un souci ?</h2>
  <p>Contactez-nous d'abord : nous trouverons une solution ensemble. Si ce n'est pas possible, vous pouvez vous adresser gratuitement au <a href="https://mediationconsommateur.be" rel="noopener">Service de médiation pour le consommateur</a> (North Gate II, boulevard du Roi Albert II 8 bte 1, 1000 Bruxelles).</p>
  <h2>Photos et textes</h2>
  <p>Les textes, photos et logos de ce site appartiennent à <?= h(load_legal()['nom_commercial']) ?>. Merci de ne pas les réutiliser sans accord.</p>
</article>
<?php page_end();
