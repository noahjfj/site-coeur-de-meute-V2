# Installer l'espace client — guide pas à pas

Ce guide explique comment mettre le site, l'espace client et l'espace gérante sur un hébergement web avec PHP et MySQL.
Les captures d'écran changent selon l'hébergeur, mais les étapes sont toujours les mêmes. Comptez environ 30 minutes.

## Ce qu'il faut

- Un hébergement web avec **PHP 8.1 ou plus** et **MySQL / MariaDB** (Hostinger « Premium », OVH « Perso », Combell…).
- Votre nom de domaine.
- Une adresse e-mail de votre domaine pour l'envoi des e-mails (ex. `noreply@votre-domaine.be`).

## 1. Créer la base de données

1. Dans le panneau de l'hébergeur, ouvrez **Bases de données MySQL** et créez une base.
2. Notez : le **nom de la base**, le **nom d'utilisateur**, le **mot de passe** (long et unique) et l'**hôte** (souvent `localhost`).
3. Ouvrez **phpMyAdmin**, choisissez la base, onglet **Importer**, et envoyez le fichier `app/schema.sql`.

## 2. Envoyer les fichiers du site

1. Ouvrez le **gestionnaire de fichiers** de l'hébergeur (ou un logiciel FTP comme FileZilla).
2. Envoyez tout le contenu du site dans le dossier public (souvent `public_html`), **y compris les fichiers `.htaccess`**.

## 3. Créer le fichier de réglages

1. Dans le dossier `app/`, copiez `config.example.php` sous le nom **`config.php`**.
2. Ouvrez `config.php` et remplissez :
   - les informations de la base (étape 1) ;
   - `base_url` : l'adresse du site, par exemple `https://www.coeurdemeute.be` ;
   - `mail_from` : l'adresse d'envoi des e-mails ;
   - `notify_email` : l'adresse de la patronne (elle reçoit les nouvelles demandes) ;
   - `setup_token` : une longue suite de lettres et chiffres au hasard (au moins 32 caractères).
3. **Ne mettez jamais ce fichier sur GitHub** : il contient des mots de passe.

## 4. Activer le HTTPS

Dans le panneau de l'hébergeur, activez le **certificat SSL gratuit** (Let's Encrypt) pour votre domaine.
Le fichier `.htaccess` redirige ensuite automatiquement tout le monde vers la version sécurisée.

## 5. Créer le compte de la patronne

1. Allez sur `https://votre-domaine.be/gerante/creer-acces.php`.
2. Entrez le code `setup_token`, l'e-mail de la patronne et un mot de passe d'au moins 14 caractères.
3. Retournez dans `app/config.php` et **videz** `setup_token` (`'setup_token' => '',`). La page de création se ferme alors définitivement.
4. Connectez-vous sur `https://votre-domaine.be/espace/connexion.php` : la patronne arrive sur son espace.

## 6. Programmer la tâche quotidienne (conseillé)

Dans le panneau de l'hébergeur, rubrique **Tâches Cron**, ajoutez une tâche **une fois par jour** :

```
php /chemin/complet/vers/public_html/app/cron.php
```

Elle envoie un rappel aux clients dont la carte journée expire dans un mois, et fait le ménage dans les données techniques.

## 7. Vérifier

- Créez un compte client de test avec une autre adresse e-mail : vous devez recevoir l'e-mail de confirmation.
  S'il n'arrive pas, regardez les indésirables, puis vérifiez `mail_from` (il doit être une adresse de votre domaine).
- Ajoutez un chien, faites une demande, confirmez-la depuis l'espace gérante.
- Essayez d'ouvrir `https://votre-domaine.be/app/config.php` : vous devez obtenir une page d'erreur (c'est voulu).
- Fixez les prix des journées et des cartes dans **Espace gérante → Tarifs**.

## Sécurité : vos bons réflexes

- Mot de passe de la patronne et de l'hébergeur : longs, uniques, jamais partagés par e-mail.
- Activez la **double authentification** sur le compte de l'hébergeur.
- Vérifiez que l'hébergeur fait des **sauvegardes automatiques** de la base (souvent inclus) ; sinon, exportez la base chaque semaine depuis phpMyAdmin.
- Gardez PHP à jour : dans le panneau de l'hébergeur, choisissez la dernière version proposée (8.2 ou plus).
- Option : pour plus de sécurité, déplacez le dossier `app/storage` hors de `public_html` et indiquez son nouveau chemin dans `storage_dir`.

## Mot de passe de la patronne oublié

Utilisez simplement **« Mot de passe oublié ? »** sur la page de connexion, comme un client.
