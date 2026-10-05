/*
 * Tarifs PAR DÉFAUT de la pension canine — Coeur de Meute
 *
 * Les prix se modifient depuis la page admin.html (connexion par identifiant
 * et mot de passe). Une fois des prix enregistrés dans l'administration, ce sont
 * eux qui s'affichent sur le site ; ce fichier ne sert plus que de valeurs de
 * secours (avant le premier enregistrement, ou hors Netlify).
 *
 *  - seuilJours     : au-delà de ce nombre de jours, le tarif long séjour s'applique
 *  - prix           : prix par jour pour un séjour court
 *  - prixLongSejour : prix par jour quand le séjour dépasse « seuilJours »
 */
window.TARIFS_PENSION = {
  "seuilJours": 5,
  "gabarits": [
    { "id": "petit", "nom": "Petit chien", "description": "Jusqu'à 10 kg", "prix": 22, "prixLongSejour": 20 },
    { "id": "moyen", "nom": "Chien moyen", "description": "De 10 à 25 kg", "prix": 26, "prixLongSejour": 24 },
    { "id": "grand", "nom": "Grand chien", "description": "Plus de 25 kg", "prix": 28, "prixLongSejour": 26 }
  ]
};
