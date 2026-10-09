<?php
/*
 * Réglages de l'espace administrateur.
 */
if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }

const SESSION_HOURS   = 12;   // durée d'une connexion
const MAX_ATTEMPTS    = 5;    // essais de mot de passe avant blocage
const LOCK_MINUTES    = 15;   // durée du blocage
const MAX_UPLOAD_MB   = 10;   // poids maximum d'une photo envoyée
const MAX_IMAGE_SIDE  = 1800; // les photos plus grandes sont réduites (en pixels)

define('SITE_DIR', dirname(__DIR__, 2));
define('ADMIN_DIR', dirname(__DIR__));

const FILE_ACCESS   = ADMIN_DIR . '/acces.php';
const FILE_SECURITY = ADMIN_DIR . '/securite.php';
const FILE_SETUP    = ADMIN_DIR . '/code-installation.php';
const FILE_TARIFS   = SITE_DIR . '/data/tarifs.json';
const FILE_PHOTOS   = SITE_DIR . '/data/photos.json';
const DIR_UPLOADS   = SITE_DIR . '/assets/uploads';
const URL_UPLOADS   = 'assets/uploads';

const DEFAULT_TARIFS = [
    'seuilJours' => 5,
    'gabarits' => [
        ['id' => 'petit', 'nom' => 'Petit chien', 'description' => "Jusqu'à 10 kg", 'prix' => 22, 'prixLongSejour' => 20],
        ['id' => 'moyen', 'nom' => 'Chien moyen', 'description' => 'De 10 à 25 kg', 'prix' => 26, 'prixLongSejour' => 24],
        ['id' => 'grand', 'nom' => 'Grand chien', 'description' => 'Plus de 25 kg', 'prix' => 30, 'prixLongSejour' => 28],
    ],
];

/*
 * Emplacements photo du site.
 * defaut : photo affichée tant qu'aucune n'est choisie (null = icône ou dessin à la place).
 * Chaque emplacement correspond à un attribut data-photo / data-photo-visual / data-photo-avatar dans les pages.
 */
const PHOTO_GROUPS = [
    'presentation' => [
        'titre' => 'Accueil & présentation',
        'emplacements' => [
            'accueil-equipe' => ['label' => 'Accueil — photo « Qui sommes-nous ? »', 'page' => 'index.html', 'defaut' => 'assets/img/devanture.jpg', 'alt' => "L'équipe de Coeur de Meute devant la pension", 'format' => 'Paysage (4:3)'],
            'qui-fondatrice' => ['label' => 'Qui sommes-nous — photo de la fondatrice', 'page' => 'qui-sommes-nous.html', 'defaut' => 'assets/img/devanture.jpg', 'alt' => "Elodie Vanrapenbush, fondatrice de Coeur de Meute", 'format' => 'Paysage (4:3)'],
        ],
    ],
    'equipe' => [
        'titre' => "L'équipe (page Qui sommes-nous)",
        'emplacements' => [
            'equipe-1' => ['label' => 'Elodie Vanrapenbush', 'page' => 'qui-sommes-nous.html', 'defaut' => null, 'alt' => 'Elodie Vanrapenbush', 'format' => 'Carré, visage centré'],
            'equipe-2' => ['label' => 'Notre équipe (soigneuses)', 'page' => 'qui-sommes-nous.html', 'defaut' => null, 'alt' => "L'équipe des soigneuses", 'format' => 'Carré'],
            'equipe-3' => ['label' => 'Dr Julien Joiret', 'page' => 'qui-sommes-nous.html', 'defaut' => null, 'alt' => 'Dr Julien Joiret', 'format' => 'Carré, visage centré'],
            'equipe-4' => ['label' => 'Nos intervenants', 'page' => 'qui-sommes-nous.html', 'defaut' => null, 'alt' => 'Nos intervenants bien-être et comportement', 'format' => 'Carré'],
        ],
    ],
    'evenements' => [
        'titre' => 'Événements (pages Actualités et Nos événements)',
        'emplacements' => [
            'evenement-noel'     => ['label' => 'Le marché de Noël', 'page' => 'evenements.html', 'defaut' => null, 'alt' => 'Le marché de Noël de Coeur de Meute', 'format' => 'Paysage'],
            'evenement-garden'   => ['label' => 'La garden party', 'page' => 'evenements.html', 'defaut' => null, 'alt' => 'La garden party de Coeur de Meute', 'format' => 'Paysage'],
            'evenement-concours' => ['label' => 'Le concours de dressage', 'page' => 'evenements.html', 'defaut' => null, 'alt' => 'Le concours de dressage de Coeur de Meute', 'format' => 'Paysage'],
        ],
    ],
];

function photo_slots(): array
{
    $all = [];
    foreach (PHOTO_GROUPS as $group) {
        $all += $group['emplacements'];
    }
    return $all;
}
