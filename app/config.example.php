<?php
/*
 * Modèle de configuration de l'espace client — Coeur de Meute
 *
 * 1. Copiez ce fichier sous le nom  app/config.php  (sur l'hébergement uniquement).
 * 2. Remplissez les valeurs ci-dessous.
 * app/config.php n'est jamais envoyé sur GitHub (voir .gitignore) : il contient des mots de passe.
 */
if (!defined('CDM')) { http_response_code(404); exit; }

return [
    // Base de données MySQL / MariaDB créée chez l'hébergeur
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'NOM_DE_LA_BASE',
        'user' => 'UTILISATEUR_DE_LA_BASE',
        'pass' => 'MOT_DE_PASSE_DE_LA_BASE',
    ],

    // Adresse du site, sans « / » à la fin (sert pour les liens dans les e-mails)
    'base_url' => 'https://www.votre-domaine.be',

    // Expéditeur des e-mails (une adresse de votre domaine, créée chez l'hébergeur)
    'mail_from'      => 'noreply@votre-domaine.be',
    'mail_from_name' => 'Coeur de Meute',

    // Adresse de la patronne : reçoit un e-mail à chaque nouvelle demande
    'notify_email' => 'contact@votre-domaine.be',

    // Code secret à usage unique pour créer le compte gérante (gerante/creer-acces.php).
    // Mettez une longue suite de caractères au hasard (au moins 32), puis videz-le une fois le compte créé.
    'setup_token' => '',

    // Dossier où sont rangés les contrats PDF (hors de la partie publique si possible)
    'storage_dir' => __DIR__ . '/storage',

    // Laisser à false en production : true affiche les erreurs détaillées
    'debug' => false,
];
