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
- `a-propos.html` — À propos (contact, plan, FAQ)

L'en-tête (menu) et le pied de page sont identiques sur toutes les pages : une modification doit être reportée dans chaque fichier.

## Espace client et espace gérante

Le site contient un espace client (`/espace/`) et un espace gérante (`/gerante/`), écrits en PHP avec une base MySQL.
Ils fonctionnent chez **n'importe quel hébergeur web avec PHP 8.1+ et MySQL/MariaDB** (Hostinger, OVH, Combell, one.com…).
Sans PHP (Netlify, GitHub Pages), les pages du site s'affichent mais pas les espaces.

- Installation pas à pas : [GUIDE-INSTALLATION.md](GUIDE-INSTALLATION.md)
- `espace/` : pages client (compte, chiens, demandes de séjour, réservations, contrats, cartes journée)
- `gerante/` : demandes à traiter, clients, cartes journée, tarifs
- `app/` : code commun et structure de la base (`app/schema.sql`), jamais accessible depuis le web
- `data/tarifs.json` : les prix de départ. Les prix modifiés depuis `/gerante/tarifs.php` sont rangés dans `app/storage/tarifs.json` (hors GitHub) et lus par le simulateur via `api/tarifs.php`
- `js/tarifs.js` : prix de secours, utilisés seulement si `data/tarifs.json` ne peut pas être lu
- `admin/` : ancienne adresse de l'admin des prix, redirige vers `/gerante/tarifs.php`

### Mettre à jour le site

Lors de l'envoi d'une nouvelle version, **ne pas écraser** sur l'hébergement :
`app/config.php` (vos réglages) et `app/storage/` (les contrats et vos prix). Ils ne sont pas dans GitHub : une mise à jour automatique depuis GitHub ne les touche pas.
