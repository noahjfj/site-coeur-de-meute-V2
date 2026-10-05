# Coeur de Meute

Site vitrine de la pension & éducation canine Coeur de Meute (Wanze).

Site statique : ouvrir `index.html` dans un navigateur.

## Pages

- `index.html` — Accueil
- `qui-sommes-nous.html` — Qui sommes-nous
- `services.html` — Nos services (vue d'ensemble)
  - `education.html` — Éducation canine
  - `pension.html` — Pension chiens & chats
  - `prevention-morsure.html` — Prévention morsure
  - `comportement-felin.html` — Comportement félin
  - `osteopathie.html` — Ostéopathie
  - `kinesiologie.html` — Kinésiologie
  - `garde-a-domicile.html` — Garde à domicile
- `galerie.html` — Galerie
- `a-propos.html` — À propos (contact, plan, FAQ)

L'en-tête (menu) et le pied de page sont identiques sur toutes les pages : une modification doit être reportée dans chaque fichier.

## Tarifs de la pension

Les prix du simulateur sont dans `js/tarifs.js`. Ils se modifient depuis la page `admin.html` (non liée dans le menu), qui enregistre directement le fichier sur GitHub grâce à une clé personnelle, ou à la main dans ce fichier.

⚠️ La page admin enregistre ses modifications directement sur GitHub : faire un `git pull` avant de modifier le site en local.
