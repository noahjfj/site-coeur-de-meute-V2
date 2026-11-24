<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/*
 * Les tarifs restent dans data/tarifs.json : le simulateur de prix du site public les lit aussi.
 * Champs ajoutés pour l'espace client : prixJournee, prixCarte10, prixCarte20 (null = pas encore fixé)
 * et le bloc « cartes ».
 */

const DEFAULT_TARIFS = [
    'seuilJours' => 5,
    'gabarits' => [
        ['id' => 'petit', 'nom' => 'Petit chien', 'description' => "Jusqu'à 10 kg", 'prix' => 22, 'prixLongSejour' => 20, 'prixJournee' => null, 'prixCarte10' => null, 'prixCarte20' => null],
        ['id' => 'moyen', 'nom' => 'Chien moyen', 'description' => 'De 10 à 25 kg', 'prix' => 26, 'prixLongSejour' => 24, 'prixJournee' => null, 'prixCarte10' => null, 'prixCarte20' => null],
        ['id' => 'grand', 'nom' => 'Grand chien', 'description' => 'Plus de 25 kg', 'prix' => 30, 'prixLongSejour' => 28, 'prixJournee' => null, 'prixCarte10' => null, 'prixCarte20' => null],
    ],
    'cartes' => ['offertes10' => 1, 'offertes20' => 2, 'validiteMois' => 12],
];

/*
 * Les prix modifiés par la gérante sont rangés dans le dossier de stockage (hors de GitHub),
 * pour qu'une mise à jour du site depuis GitHub ne les écrase jamais.
 * data/tarifs.json (dans GitHub) ne sert que de valeurs de départ.
 */
function tarifs_file(): string
{
    return rtrim((string) config('storage_dir', APP_DIR . '/storage'), '/') . '/tarifs.json';
}

function default_tarifs_file(): string
{
    return SITE_DIR . '/data/tarifs.json';
}

function load_tarifs(): array
{
    $t = DEFAULT_TARIFS;
    $file = is_file(tarifs_file()) ? tarifs_file() : default_tarifs_file();
    $raw = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    if (is_array($raw)) {
        if (isset($raw['seuilJours']) && is_numeric($raw['seuilJours'])) {
            $t['seuilJours'] = (int) $raw['seuilJours'];
        }
        foreach ($t['gabarits'] as $i => $g) {
            foreach (($raw['gabarits'] ?? []) as $rg) {
                if (is_array($rg) && ($rg['id'] ?? null) === $g['id']) {
                    $t['gabarits'][$i] = array_merge($g, array_intersect_key($rg, $g));
                }
            }
        }
        if (isset($raw['cartes']) && is_array($raw['cartes'])) {
            $t['cartes'] = array_merge($t['cartes'], array_intersect_key($raw['cartes'], $t['cartes']));
        }
    }
    return $t;
}

function tarif_for(string $size): array
{
    foreach (load_tarifs()['gabarits'] as $g) {
        if ($g['id'] === $size) {
            return $g;
        }
    }
    throw new RuntimeException('Gabarit inconnu');
}

function save_tarifs(array $t): bool
{
    if (!is_dir(dirname(tarifs_file()))) {
        mkdir(dirname(tarifs_file()), 0750, true);
    }
    $json = json_encode($t, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    $tmp = tarifs_file() . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!rename($tmp, tarifs_file())) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** Estimation d'un séjour : [jours, prix par jour, total]. */
function estimate_stay(string $size, string $from, string $to): array
{
    $t = load_tarifs();
    $g = tarif_for($size);
    $days = max(1, days_between($from, $to));
    $rate = $days > $t['seuilJours'] ? $g['prixLongSejour'] : $g['prix'];
    return [$days, $rate, $days * $rate];
}
