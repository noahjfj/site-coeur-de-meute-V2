/*
 * Tarifs de SECOURS de la pension canine — Coeur de Meute
 *
 * Les vrais prix sont dans data/tarifs.json et se modifient depuis l'espace
 * administrateur (dossier admin/). Ce fichier ne sert que si data/tarifs.json
 * ne peut pas être lu (par exemple en ouvrant le site directement depuis
 * l'ordinateur, sans serveur).
 */
window.TARIFS_PENSION = {
  "seuilJours": 5,
  "gabarits": [
    { "id": "petit", "nom": "Petit chien", "description": "Jusqu'à 10 kg", "prix": 22, "prixLongSejour": 20 },
    { "id": "moyen", "nom": "Chien moyen", "description": "De 10 à 25 kg", "prix": 26, "prixLongSejour": 24 },
    { "id": "grand", "nom": "Grand chien", "description": "Plus de 25 kg", "prix": 30, "prixLongSejour": 28 }
  ]
};
