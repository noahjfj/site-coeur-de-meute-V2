# Coeur de Meute

> Version HTML du site (sans WordPress). La version WordPress se trouve sur la branche `claude/coeur-de-meute-website-qw6a8e`.

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
- `actualites.html` — Actualités (vue d'ensemble)
  - `evenements.html` — Nos événements
  - `presse.html` — Presse
- `a-propos.html` — À propos (contact, plan, FAQ)

L'en-tête (menu) et le pied de page sont identiques sur toutes les pages : une modification doit être reportée dans chaque fichier.

## Espace administrateur (`/admin/`)

Le site fonctionne chez **n'importe quel hébergeur web avec PHP** (OVH, Hostinger, one.com, Infomaniak, etc.).
Pour l'installer ou le déménager : copier **tout le dossier** sur l'hébergement (FTP ou gestionnaire de fichiers).

L'espace administrateur permet de modifier :
- **les tarifs de la pension** → `data/tarifs.json` (utilisé par le simulateur) ;
- **les photos du site** (accueil, fondatrice, équipe, événements) et **la galerie** → `data/photos.json` + `assets/uploads/` ;
- **le mot de passe**.

Fichiers : `admin/index.php` (point d'entrée), `admin/inc/` (configuration et logique), `admin/views/` (écrans).
Les emplacements photo se règlent dans `admin/inc/config.php` (constante `PHOTO_GROUPS`) et correspondent aux attributs
`data-photo`, `data-photo-visual`, `data-photo-avatar` et `data-gallery` des pages, lus par `js/photos.js`.

### Première connexion

1. Ouvrir `https://votre-site/admin/` : la page demande un **code d'installation**.
2. Ce code est écrit dans le fichier `admin/code-installation.php`, à ouvrir avec le gestionnaire de fichiers de l'hébergeur.
3. Saisir le code, choisir l'identifiant et le mot de passe (10 caractères minimum). Le fichier du code est ensuite supprimé.

**Mot de passe oublié :** supprimer `admin/acces.php` sur l'hébergement, puis revenir sur `/admin/` (un nouveau code est créé).

### Sécurité

- Mot de passe chiffré, session limitée à 12 h, protection CSRF, en-têtes de sécurité.
- Blocage de 15 minutes après 5 mots de passe erronés.
- Photos : formats JPG, PNG, WEBP uniquement (contrôle du contenu réel), réduites à 1800 px et ré-encodées ;
  aucun script ne peut s'exécuter dans `assets/uploads/`.
- Fichiers privés (`admin/acces.php`, `admin/securite.php`, `admin/code-installation.php`) illisibles depuis le web.

### Mettre à jour ou déménager le site

- Les dossiers `data/`, `admin/` et `assets/uploads/` doivent être modifiables par le serveur (c'est le cas par défaut chez la plupart des hébergeurs).
- Lors de l'envoi d'une nouvelle version du site, **ne pas écraser** `data/tarifs.json`, `data/photos.json`, le dossier
  `assets/uploads/` (vos photos) ni `admin/acces.php` (votre accès) : récupérez-les d'abord depuis l'hébergement, ou ne les envoyez pas.
- Sans PHP (GitHub Pages, Netlify…), le site et le simulateur fonctionnent, mais pas l'espace administrateur.
