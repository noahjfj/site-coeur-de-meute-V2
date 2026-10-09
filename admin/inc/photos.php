<?php
/*
 * Photos du site : emplacements fixes + galerie, enregistrés dans ../data/photos.json.
 */
if (!defined('CDM_ADMIN')) { http_response_code(404); exit; }

const ALLOWED_IMAGES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

function load_photos(): array
{
    $data = read_json(FILE_PHOTOS) ?? [];
    return [
        'emplacements' => is_array($data['emplacements'] ?? null) ? $data['emplacements'] : [],
        'galerie'      => is_array($data['galerie'] ?? null) ? array_values($data['galerie']) : [],
        'misAJour'     => $data['misAJour'] ?? null,
    ];
}

function save_photos(array $photos): bool
{
    $photos['misAJour'] = date('c');
    return write_json(FILE_PHOTOS, $photos);
}

/** Photo actuellement affichée pour un emplacement (choisie, sinon celle d'origine). */
function slot_current(array $photos, string $key): ?array
{
    $slots = photo_slots();
    if (!empty($photos['emplacements'][$key]['src'])) {
        return $photos['emplacements'][$key];
    }
    $def = $slots[$key]['defaut'] ?? null;
    return $def ? ['src' => $def, 'alt' => $slots[$key]['alt'], 'origine' => true] : null;
}

/** Supprime un fichier envoyé depuis l'admin (jamais les images d'origine du site). */
function delete_upload(?string $src): void
{
    if (!$src || strpos($src, URL_UPLOADS . '/') !== 0) {
        return;
    }
    $file = DIR_UPLOADS . '/' . basename($src);
    if (is_file($file)) {
        @unlink($file);
    }
}

/** Liste des fichiers envoyés via un champ <input type="file" multiple> */
function uploaded_files(string $field): array
{
    if (empty($_FILES[$field])) {
        return [];
    }
    $f = $_FILES[$field];
    if (!is_array($f['name'])) {
        return [$f];
    }
    $list = [];
    foreach ($f['name'] as $i => $name) {
        $list[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $list;
}

/**
 * Vérifie, réduit et enregistre une photo envoyée.
 * Renvoie le chemin public (assets/uploads/…) ou lève une exception avec un message clair.
 */
function store_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Aucune photo sélectionnée.');
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        throw new RuntimeException('Photo trop lourde (' . MAX_UPLOAD_MB . ' Mo maximum).');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException("L'envoi de la photo a échoué. Réessayez.");
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_IMAGES[$mime]) || !@getimagesize($file['tmp_name'])) {
        throw new RuntimeException('Format non accepté : utilisez une photo JPG, PNG ou WEBP.');
    }

    if (!is_dir(DIR_UPLOADS) && !@mkdir(DIR_UPLOADS, 0755, true)) {
        throw new RuntimeException("Le dossier des photos n'est pas modifiable par le serveur. Contactez votre hébergeur.");
    }

    $ext  = ALLOWED_IMAGES[$mime];
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = DIR_UPLOADS . '/' . $name;

    if (!process_image($file['tmp_name'], $mime, $dest) && !move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Impossible d'enregistrer la photo. Vérifiez que le dossier assets/uploads est modifiable.");
    }
    @chmod($dest, 0644);
    return URL_UPLOADS . '/' . $name;
}

/**
 * Réduit la photo si elle est trop grande, la remet dans le bon sens et la ré-encode
 * (ce qui retire aussi les données cachées). Renvoie false si GD n'est pas disponible.
 */
function process_image(string $src, string $mime, string $dest): bool
{
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    $image = null;
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $image = @imagecreatefromjpeg($src);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $image = @imagecreatefrompng($src);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($src);
    }
    if (!$image) {
        return false;
    }

    // Photos de téléphone : on applique l'orientation enregistrée par l'appareil
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($src);
        $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($angle) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated) {
                imagedestroy($image);
                $image = $rotated;
            }
        }
    }

    $w = imagesx($image);
    $h = imagesy($image);
    $ratio = min(1, MAX_IMAGE_SIDE / max($w, $h));
    if ($ratio < 1) {
        $nw = (int) round($w * $ratio);
        $nh = (int) round($h * $ratio);
        $resized = imagecreatetruecolor($nw, $nh);
        if ($mime !== 'image/jpeg') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($image);
        $image = $resized;
    } elseif ($mime !== 'image/jpeg') {
        imagesavealpha($image, true);
    }

    $ok = false;
    if ($mime === 'image/jpeg') {
        imageinterlace($image, true);
        $ok = imagejpeg($image, $dest, 82);
    } elseif ($mime === 'image/png') {
        $ok = imagepng($image, $dest, 7);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        $ok = imagewebp($image, $dest, 82);
    }
    imagedestroy($image);
    return (bool) $ok;
}
