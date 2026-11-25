<?php
/* Politique de confidentialité (RGPD). Les coordonnées viennent de la page « Infos légales » de la gérante. */
require __DIR__ . '/../app/bootstrap.php';
$area = current_user() ? (current_user()['role'] === 'gerante' ? 'gerante' : 'client') : 'public';
$l = load_legal();
page_start('Vos données', $area);
?>
<article class="card prose">
  <p class="eyebrow">Confidentialité</p>
  <h1>Vos données personnelles</h1>
  <p class="muted">Mise à jour le 25 novembre 2026.</p>

  <h2>Qui est responsable de vos données ?</h2>
  <?= legal_identity_html() ?>
  <p>Pour toute question sur vos données, écrivez-nous<?= $l['email'] !== '' ? ' à <a href="mailto:' . h($l['email']) . '">' . h($l['email']) . '</a>' : '' ?>. Nous répondons dans un délai d'un mois maximum.</p>

  <h2>Quelles données, pourquoi, et sur quelle base ?</h2>
  <div class="table-box">
  <table class="table">
    <thead><tr><th>Données</th><th>Pourquoi</th><th>Base légale</th></tr></thead>
    <tbody>
      <tr><td>Nom, e-mail, téléphone, adresse, mot de passe (chiffré)</td><td>Votre compte, vos réservations, vous contacter</td><td>Le contrat avec vous</td></tr>
      <tr><td>Fiches de vos chiens : race, âge, santé, vaccins, vétérinaire, caractère, photo</td><td>Prendre soin de votre chien et le reconnaître</td><td>Le contrat avec vous</td></tr>
      <tr><td>Demandes, cartes journée, acomptes et paiements, contrats signés</td><td>Organiser les séjours, la facturation et la comptabilité</td><td>Le contrat, et nos obligations comptables</td></tr>
      <tr><td>Journal technique des connexions (date, adresse IP)</td><td>Protéger votre compte contre le piratage</td><td>Notre intérêt légitime : la sécurité</td></tr>
      <tr><td>Statistiques de visite du site public (si vous les acceptez)</td><td>Savoir quelles pages sont lues pour améliorer le site</td><td>Votre accord</td></tr>
    </tbody>
  </table>
  </div>
  <p>Vos données ne sont jamais vendues. Nous ne vous envoyons pas de publicité.</p>

  <h2>Qui d'autre y a accès ?</h2>
  <p>Seule la gérante de la pension voit vos données. Pour faire fonctionner le site, nous utilisons quelques prestataires, liés par contrat aux règles du RGPD :</p>
  <ul>
    <li><strong>Hostinger</strong> (hébergement du site, de la base de données et des e-mails), serveurs dans l'Union européenne.</li>
    <li><strong>Google</strong> : l'agenda de la gérante reçoit les séjours confirmés (nom du chien, votre nom et votre téléphone), pour organiser la pension. Et, seulement si vous les acceptez, les statistiques de visite du site public (Google Tag Manager / Google Analytics).</li>
    <li>Un vétérinaire, uniquement en cas d'urgence concernant votre chien.</li>
  </ul>
  <p>Google peut traiter certaines données hors de l'Union européenne ; il le fait dans le cadre des garanties prévues par la Commission européenne (Data Privacy Framework et clauses contractuelles types).</p>

  <h2>Combien de temps les gardons-nous ?</h2>
  <ul>
    <li><strong>Votre compte et les fiches de vos chiens</strong> : tant que votre compte existe. Vous pouvez le supprimer vous-même à tout moment dans « Mon compte ».</li>
    <li><strong>Ce qu'il faut pour la comptabilité</strong> (votre nom et votre adresse, le nom du chien, les dates et les montants payés) : <?= ACCOUNTING_YEARS ?> ans, comme la loi comptable l'exige, même si vous supprimez votre compte. Rien d'autre n'est gardé.</li>
    <li><strong>Journal des connexions</strong> : 1 an.</li>
    <li><strong>Liens envoyés par e-mail</strong> (confirmation, mot de passe oublié) : quelques jours.</li>
  </ul>

  <h2>Comment sont-elles protégées ?</h2>
  <p>Connexion chiffrée (HTTPS), mots de passe chiffrés et illisibles même pour nous, protection contre les essais de mot de passe à répétition. Chaque client ne voit que ses propres données. Les photos que vous envoyez sont nettoyées : la position GPS cachée dans les photos de téléphone est effacée.</p>

  <h2>Les photos de votre chien</h2>
  <p>La photo de la fiche de votre chien reste privée. Nous ne publions une photo de votre chien sur le site ou les réseaux sociaux que si vous l'avez accepté dans « Mon compte ». Vous pouvez retirer cet accord à tout moment.</p>

  <h2 id="cookies">Cookies</h2>
  <ul>
    <li><strong>Espace client</strong> : un seul cookie, indispensable pour rester connecté. Il disparaît quand vous vous déconnectez ou fermez le navigateur.</li>
    <li><strong>Site public</strong> : des cookies de statistiques (Google) sont utilisés <strong>seulement si vous cliquez « Accepter »</strong> dans la fenêtre qui s'affiche. Votre choix est gardé 6 mois. Vous pouvez le changer à tout moment avec le lien « Cookies » en bas des pages du site.</li>
  </ul>
  <p>Les polices d'écriture sont hébergées sur notre propre site : aucune information n'est envoyée à Google pour les afficher.</p>

  <h2>Vos droits</h2>
  <p>Vous pouvez à tout moment : consulter vos données, les corriger, les faire effacer, en recevoir une copie, limiter ou refuser certaines utilisations, et retirer votre accord (photos, statistiques). La plupart se font directement dans votre espace client ; pour le reste, écrivez-nous.</p>
  <p>Si vous pensez que vos données sont mal utilisées, vous pouvez porter plainte auprès de l'<a href="https://www.autoriteprotectiondonnees.be" rel="noopener">Autorité de protection des données</a>, rue de la Presse 35, 1000 Bruxelles.</p>
</article>
<?php page_end();
