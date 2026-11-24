<?php
require __DIR__ . '/../app/bootstrap.php';
page_start('Vos données', current_user() && current_user()['role'] === 'client' ? 'client' : 'public');
?>
<article class="card prose">
  <p class="eyebrow">Confidentialité</p>
  <h1>Vos données personnelles</h1>
  <p class="flash flash--info">Texte de base à faire relire avant la mise en ligne : il doit correspondre à la réalité de la pension.</p>
  <h2>Qui est responsable ?</h2>
  <p>Coeur de Meute, pension et éducation canine, Wanze (Belgique). Contact : via la page <a href="/a-propos.html">À propos</a>.</p>
  <h2>Quelles données et pourquoi ?</h2>
  <ul>
    <li>Vos coordonnées (nom, e-mail, téléphone, adresse) : pour gérer votre compte, vos demandes et vous contacter.</li>
    <li>Les fiches de vos chiens (santé, vaccins, vétérinaire, caractère) : pour prendre soin d'eux pendant leur séjour.</li>
    <li>Vos demandes, cartes journée et contrats : pour organiser les séjours et la facturation.</li>
    <li>Un journal technique des connexions (date, adresse IP) : pour la sécurité de votre compte.</li>
  </ul>
  <p>Ces données ne sont ni vendues ni partagées, sauf obligation légale ou urgence vétérinaire concernant votre chien.</p>
  <h2>Où sont-elles stockées ?</h2>
  <p>Chez notre hébergeur web, sur des serveurs situés dans l'Union européenne. Les mots de passe sont chiffrés et illisibles, même pour nous.</p>
  <h2>Combien de temps ?</h2>
  <p>Tant que votre compte existe. Vous pouvez le supprimer vous-même à tout moment depuis « Mon compte » : tout est alors effacé.</p>
  <h2>Vos droits</h2>
  <p>Vous pouvez consulter, corriger ou supprimer vos données, et vous opposer à leur utilisation. Contactez la pension pour toute question. Vous pouvez aussi vous adresser à l'Autorité de protection des données (autoriteprotectiondonnees.be).</p>
</article>
<?php page_end();
