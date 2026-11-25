<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/*
 * Photos envoyées depuis un téléphone (chien, contrat photographié).
 * Chaque image est vérifiée puis REDESSINÉE en JPEG par le serveur :
 * - un faux fichier (script déguisé en image) ne survit pas ;
 * - les informations cachées (position GPS, modèle du téléphone…) disparaissent ;
 * - la photo est tournée dans le bon sens et réduite pour rester légère.
 */

const IMAGE_MAX_BYTES = 15 * 1024 * 1024;
const IMAGE_MAX_PIXELS = 50_000_000;

/** Enregistre la photo en JPEG dans $dest. Retourne null si tout va bien, sinon un message d'erreur. */
function save_image_upload(array $file, string $dest, int $maxSide): ?string
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return 'La photo est trop lourde (15 Mo maximum).';
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return 'La photo n\'a pas pu être reçue.';
    }
    if ($file['size'] > IMAGE_MAX_BYTES) {
        return 'La photo est trop lourde (15 Mo maximum).';
    }
    if (!function_exists('imagecreatefromstring')) {
        return 'Les photos ne sont pas disponibles sur cet hébergement (extension GD manquante).';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return $mime === 'image/heic' || $mime === 'image/heif'
            ? 'Ce format de photo (HEIC) n\'est pas lu. Dans les réglages de l\'appareil photo de l\'iPhone, choisissez « Le plus compatible », ou envoyez une capture d\'écran.'
            : 'Seules les photos JPEG, PNG ou WebP sont acceptées.';
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > IMAGE_MAX_PIXELS) {
        return 'Cette image n\'est pas lisible.';
    }
    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        return 'Cette image n\'est pas lisible.';
    }
    // Remet la photo dans le bon sens (les téléphones l'indiquent dans les infos EXIF)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $rot = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($rot) {
            $turned = imagerotate($src, $rot, 0);
            if ($turned) {
                imagedestroy($src);
                $src = $turned;
            }
        }
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255)); // fond blanc pour les PNG transparents
    imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0750, true);
    }
    $ok = imagejpeg($out, $dest, 84);
    imagedestroy($out);
    if (!$ok) {
        return 'La photo n\'a pas pu être enregistrée.';
    }
    chmod($dest, 0640);
    return null;
}

/* ---------- Photos des chiens ---------- */

function dog_photos_dir(): string
{
    return rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/photos-chiens';
}

function dog_photo_path(string $stored): string
{
    if (!preg_match('/^[a-f0-9]{64}$/', $stored)) {
        throw new RuntimeException('Nom de photo invalide');
    }
    return dog_photos_dir() . '/' . $stored . '.jpg';
}

/** Remplace la photo d'un chien (déjà vérifié comme appartenant au bon client). */
function set_dog_photo(array $dog, array $file): ?string
{
    $stored = bin2hex(random_bytes(32));
    $err = save_image_upload($file, dog_photo_path($stored), 1000);
    if ($err) {
        return $err;
    }
    q('UPDATE dogs SET photo = ? WHERE id = ?', [$stored, $dog['id']]);
    if (!empty($dog['photo'])) {
        @unlink(dog_photo_path($dog['photo']));
    }
    return null;
}

function remove_dog_photo(array $dog): void
{
    if (!empty($dog['photo'])) {
        @unlink(dog_photo_path($dog['photo']));
        q('UPDATE dogs SET photo = NULL WHERE id = ?', [$dog['id']]);
    }
}

/** Adresse de la photo (servie par un script qui vérifie qui la demande). */
function dog_photo_url(array $dog): ?string
{
    return !empty($dog['photo']) ? '/espace/photo-chien.php?id=' . (int) $dog['id'] . '&v=' . substr($dog['photo'], 0, 8) : null;
}
