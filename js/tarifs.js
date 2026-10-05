/*
 * Tarifs de la pension canine — Coeur de Meute
 *
 * Ce fichier est modifié automatiquement par la page admin.html.
 * Vous pouvez aussi le modifier à la main : changez uniquement les nombres
 * et les textes entre guillemets.
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
