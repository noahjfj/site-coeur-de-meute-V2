<?php
/*
 * Traitement des formulaires de l'espace administrateur.
 * Chaque action se termine par une redirection (pas de double envoi en actualisant la page).
 */
if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }

function handle_post(): void
{
    $action = (string) ($_POST['action'] ?? '');
    $page   = (string) ($_POST['return'] ?? '');

    if (!csrf_ok()) {
        flash('error', 'La page a expiré. Veuillez réessayer.');
        redirect_to($page);
    }

    // ----- Actions possibles sans être connecté -----
    if ($action === 'setup') {
        action_setup();
    }
    if ($action === 'login') {
        action_login();
    }
    if (!is_logged_in()) {
        flash('error', 'Votre session a expiré, reconnectez-vous.');
        redirect_to();
    }

    // ----- Actions réservées à l'administratrice connectée -----
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('success', 'Vous êtes déconnectée.');
            redirect_to();
        case 'save_tarifs':
            action_save_tarifs();
        case 'slot_save':
            action_slot_save();
        case 'slot_reset':
            action_slot_reset();
        case 'gallery_add':
            action_gallery_add();
        case 'gallery_update':
            action_gallery_update();
        case 'gallery_move':
            action_gallery_move();
        case 'gallery_delete':
            action_gallery_delete();
        case 'password':
            action_password();
    }
    redirect_to($page);
}

function action_setup(): void
{
    if (load_access() !== null) {
        redirect_to();
    }
    if (lock_remaining() > 0) {
        redirect_to();
    }
    $code    = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $user    = trim((string) ($_POST['user'] ?? ''));
    $pass    = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    $_SESSION['old_user'] = $user;

    $expected = read_setup_code();
    if ($expected === '' || !hash_equals($expected, $code)) {
        record_failure();
        sleep(1);
        flash('error', "Code d'installation incorrect.");
        redirect_to();
    }
    if ($user === '' || text_len($user) > 50) {
        flash('error', 'Choisissez un identifiant (50 caractères maximum).');
        redirect_to();
    }
    if (text_len($pass) < 10) {
        flash('error', 'Le mot de passe doit contenir au moins 10 caractères.');
        redirect_to();
    }
    if ($pass !== $confirm) {
        flash('error', 'Les deux mots de passe ne sont pas identiques.');
        redirect_to();
    }
    if (!save_access($user, $pass)) {
        flash('error', "Impossible d'enregistrer l'accès : le dossier « admin » n'est pas modifiable par le serveur. Contactez votre hébergeur.");
        redirect_to();
    }
    @unlink(FILE_SETUP);
    clear_failures();
    unset($_SESSION['old_user']);
    log_in();
    flash('success', 'Votre accès est créé. Bienvenue dans votre espace administrateur !');
    redirect_to('accueil');
}

function action_login(): void
{
    $access = load_access();
    if ($access === null) {
        redirect_to();
    }
    $wait = lock_remaining();
    if ($wait > 0) {
        flash('error', 'Trop de tentatives. Réessayez dans ' . (int) ceil($wait / 60) . ' minute(s).');
        redirect_to();
    }
    $user = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $_SESSION['old_user'] = $user;

    if (hash_equals($access['user'], $user) && password_verify($pass, $access['hash'])) {
        clear_failures();
        unset($_SESSION['old_user']);
        log_in();
        redirect_to('accueil');
    }

    record_failure();
    sleep(1);
    $wait = lock_remaining();
    if ($wait > 0) {
        flash('error', 'Trop de tentatives : l\'accès est bloqué pendant ' . LOCK_MINUTES . ' minutes.');
    } else {
        $left = attempts_left();
        flash('error', 'Identifiant ou mot de passe incorrect. ' . $left . ' essai' . ($left > 1 ? 's' : '') . ' restant' . ($left > 1 ? 's' : '') . ' avant blocage.');
    }
    redirect_to();
}

function action_save_tarifs(): void
{
    $seuil = filter_var($_POST['seuilJours'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 60]]);
    if ($seuil === false) {
        flash('error', 'Le nombre de jours doit être un nombre entier entre 1 et 60.');
        redirect_to('tarifs');
    }
    $gabarits = [];
    foreach ((array) ($_POST['id'] ?? []) as $i => $id) {
        $nom  = trim((string) ($_POST['nom'][$i] ?? ''));
        $desc = trim((string) ($_POST['description'][$i] ?? ''));
        $prix = to_number((string) ($_POST['prix'][$i] ?? ''));
        $long = to_number((string) ($_POST['prixLongSejour'][$i] ?? ''));
        if ($nom === '' || text_len($nom) > 40 || text_len($desc) > 60) {
            flash('error', 'Chaque gabarit doit avoir un nom (40 caractères max.) et une description courte.');
            redirect_to('tarifs');
        }
        if ($prix <= 0 || $long <= 0 || $prix > 1000 || $long > 1000) {
            flash('error', "Les prix de « {$nom} » doivent être compris entre 0 et 1000 €.");
            redirect_to('tarifs');
        }
        $gabarits[] = [
            'id'             => preg_replace('/[^a-z0-9-]/', '', strtolower((string) $id)) ?: 'gabarit-' . ($i + 1),
            'nom'            => $nom,
            'description'    => $desc,
            'prix'           => round($prix, 2),
            'prixLongSejour' => round($long, 2),
        ];
    }
    if (!$gabarits) {
        redirect_to('tarifs');
    }
    if (!write_json(FILE_TARIFS, ['seuilJours' => $seuil, 'gabarits' => $gabarits, 'misAJour' => date('c')])) {
        flash('error', "Impossible d'enregistrer : le dossier « data » n'est pas modifiable par le serveur. Contactez votre hébergeur.");
        redirect_to('tarifs');
    }
    flash('success', 'Prix enregistrés ! Ils sont déjà visibles sur le site.');
    redirect_to('tarifs');
}

function slot_key_from_post(): string
{
    $key = (string) ($_POST['slot'] ?? '');
    if (!isset(photo_slots()[$key])) {
        flash('error', 'Emplacement photo inconnu.');
        redirect_to('photos');
    }
    return $key;
}

function action_slot_save(): void
{
    $key    = slot_key_from_post();
    $slot   = photo_slots()[$key];
    $photos = load_photos();
    $alt    = trim((string) ($_POST['alt'] ?? ''));
    $alt    = $alt !== '' ? text_cut($alt, 150) : $slot['alt'];
    $file   = uploaded_files('photo')[0] ?? null;
    $hasNew = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($hasNew) {
        try {
            $src = store_upload($file);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect_to('photos', [], 'photo-' . $key);
        }
        delete_upload($photos['emplacements'][$key]['src'] ?? null);
        $photos['emplacements'][$key] = ['src' => $src, 'alt' => $alt];
        $message = 'Photo « ' . $slot['label'] . ' » remplacée.';
    } elseif (!empty($photos['emplacements'][$key]['src'])) {
        $photos['emplacements'][$key]['alt'] = $alt;
        $message = 'Description de la photo enregistrée.';
    } else {
        flash('error', 'Choisissez d\'abord une photo sur votre appareil.');
        redirect_to('photos', [], 'photo-' . $key);
    }
    if (!save_photos($photos)) {
        flash('error', "Impossible d'enregistrer : le dossier « data » n'est pas modifiable par le serveur.");
        redirect_to('photos', [], 'photo-' . $key);
    }
    flash('success', $message . ' Elle est déjà visible sur le site.');
    redirect_to('photos', [], 'photo-' . $key);
}

function action_slot_reset(): void
{
    $key    = slot_key_from_post();
    $photos = load_photos();
    delete_upload($photos['emplacements'][$key]['src'] ?? null);
    unset($photos['emplacements'][$key]);
    save_photos($photos);
    $slot = photo_slots()[$key];
    flash('success', $slot['defaut'] ? 'La photo d\'origine est rétablie.' : 'Photo retirée : le dessin d\'origine est rétabli.');
    redirect_to('photos', [], 'photo-' . $key);
}

function action_gallery_add(): void
{
    $photos = load_photos();
    $added = 0;
    $errors = [];
    foreach (uploaded_files('photos') as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        try {
            $src = store_upload($file);
            $legende = pathinfo((string) $file['name'], PATHINFO_FILENAME);
            $legende = preg_match('/^(IMG|DSC|PXL|WhatsApp|Screenshot|image)[-_ ]?\d*/i', $legende) ? '' : text_cut(str_replace(['-', '_'], ' ', $legende), 120);
            $photos['galerie'][] = ['src' => $src, 'legende' => $legende];
            $added++;
        } catch (RuntimeException $e) {
            $errors[] = $file['name'] . ' : ' . $e->getMessage();
        }
    }
    if ($added) {
        save_photos($photos);
        flash('success', $added . ' photo' . ($added > 1 ? 's ajoutées' : ' ajoutée') . ' à la galerie. Vous pouvez maintenant leur donner une légende.');
    }
    foreach ($errors as $err) {
        flash('error', $err);
    }
    if (!$added && !$errors) {
        flash('error', 'Choisissez d\'abord une ou plusieurs photos sur votre appareil.');
    }
    redirect_to('photos', [], 'galerie');
}

function action_gallery_update(): void
{
    $photos = load_photos();
    foreach ((array) ($_POST['legende'] ?? []) as $i => $legende) {
        if (isset($photos['galerie'][(int) $i])) {
            $photos['galerie'][(int) $i]['legende'] = text_cut(trim((string) $legende), 120);
        }
    }
    save_photos($photos);
    flash('success', 'Légendes enregistrées.');
    redirect_to('photos', [], 'galerie');
}

function action_gallery_move(): void
{
    $photos = load_photos();
    $i = (int) ($_POST['index'] ?? -1);
    $j = $i + (($_POST['direction'] ?? '') === 'up' ? -1 : 1);
    if (isset($photos['galerie'][$i], $photos['galerie'][$j])) {
        [$photos['galerie'][$i], $photos['galerie'][$j]] = [$photos['galerie'][$j], $photos['galerie'][$i]];
        save_photos($photos);
    }
    redirect_to('photos', [], 'galerie');
}

function action_gallery_delete(): void
{
    $photos = load_photos();
    $i = (int) ($_POST['index'] ?? -1);
    if (isset($photos['galerie'][$i])) {
        delete_upload($photos['galerie'][$i]['src']);
        array_splice($photos['galerie'], $i, 1);
        save_photos($photos);
        flash('success', 'Photo retirée de la galerie.');
    }
    redirect_to('photos', [], 'galerie');
}

function action_password(): void
{
    $access  = load_access();
    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    if (!$access || !password_verify($current, $access['hash'])) {
        sleep(1);
        flash('error', 'Le mot de passe actuel est incorrect.');
    } elseif (text_len($new) < 10) {
        flash('error', 'Le nouveau mot de passe doit contenir au moins 10 caractères.');
    } elseif ($new !== $confirm) {
        flash('error', 'Les deux nouveaux mots de passe ne sont pas identiques.');
    } elseif (!save_access($access['user'], $new)) {
        flash('error', "Impossible d'enregistrer le nouveau mot de passe (dossier « admin » non modifiable).");
    } else {
        flash('success', 'Mot de passe modifié.');
    }
    redirect_to('compte');
}
