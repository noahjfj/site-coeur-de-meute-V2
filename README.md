# Coeur de Meute

> **Version WordPress :** le thème complet se trouve dans `wordpress/coeur-de-meute/` (mode d'emploi : `wordpress/coeur-de-meute/LISEZMOI.md`).
> Le site HTML ci-dessous reste disponible (aperçu GitHub Pages).

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

## Tarifs de la pension et espace administrateur

Le site fonctionne chez **n'importe quel hébergeur web avec PHP** (OVH, Hostinger, one.com, Infomaniak, etc.).
Pour l'installer ou le déménager : copier **tout le dossier** sur l'hébergement (par FTP ou le gestionnaire de fichiers de l'hébergeur).

- `data/tarifs.json` : les prix affichés par le simulateur.
- `admin/` : espace administrateur (`https://votre-site/admin/`) pour modifier les prix.
- `js/tarifs.js` : prix de secours, utilisés seulement si `data/tarifs.json` ne peut pas être lu.

### Première connexion

Ouvrir `https://votre-site/admin/` : la page propose de **créer l'identifiant et le mot de passe**.
Ils sont enregistrés, chiffrés, dans `admin/acces.php` (fichier créé sur le serveur, illisible depuis le web).

**Mot de passe oublié :** supprimer `admin/acces.php` sur l'hébergement, puis retourner sur `/admin/` pour recréer un accès.

### Mettre à jour ou déménager le site

- Les dossiers `data/` et `admin/` doivent être modifiables par le serveur (c'est le cas par défaut chez la plupart des hébergeurs).
- Lors de l'envoi d'une nouvelle version du site, **ne pas écraser** `data/tarifs.json` (vos prix) ni `admin/acces.php` (votre accès) :
  récupérez-les d'abord depuis l'hébergement, ou n'envoyez pas ces deux fichiers.
- Sans PHP (GitHub Pages, Netlify…), le site et le simulateur fonctionnent, mais pas l'espace administrateur.
