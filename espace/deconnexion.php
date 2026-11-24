<?php
require __DIR__ . '/../app/bootstrap.php';

// La déconnexion se fait uniquement par un formulaire (POST + jeton), jamais par un simple lien.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/espace/');
}
if ($u = current_user()) {
    audit((int) $u['id'], 'logout');
}
end_session();
session_start();
flash('info', 'Vous êtes déconnecté. À bientôt !');
redirect('/espace/connexion.php');
