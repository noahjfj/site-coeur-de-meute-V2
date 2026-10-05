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

## Tarifs de la pension (administration)

Le site est prévu pour être hébergé sur **Netlify** (voir `netlify.toml`).

- `admin.html` (non liée dans le menu) : connexion par identifiant / mot de passe, puis modification des prix.
- `netlify/functions/login.mjs` → `POST /api/login` : vérifie les identifiants, renvoie une session de 12 h.
- `netlify/functions/tarifs.mjs` → `GET /api/tarifs` (public) et `PUT /api/tarifs` (connecté) : les prix sont stockés dans Netlify Blobs.
- `js/tarifs.js` : prix **par défaut**, utilisés avant le premier enregistrement ou hors Netlify (GitHub Pages, aperçu local).

### Configuration sur Netlify

Dans *Site configuration → Environment variables*, créer :

| Variable | Valeur |
| --- | --- |
| `ADMIN_USER` | l'identifiant de connexion |
| `ADMIN_PASSWORD` | le mot de passe (long et difficile à deviner) |

Puis redéployer le site (*Deploys → Trigger deploy*).
