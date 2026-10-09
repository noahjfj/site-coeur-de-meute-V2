<?php
/*
 * Espace administrateur — Coeur de Meute
 * Modification des tarifs de la pension (enregistrés dans ../data/tarifs.json).
 *
 * Première visite : création de l'identifiant et du mot de passe (stockés, chiffrés, dans acces.php).
 * Mot de passe oublié : supprimez le fichier admin/acces.php par FTP, puis recréez un accès.
 */

declare(strict_types=1);

const SESSION_HOURS = 12;
$ACCESS_FILE = __DIR__ . '/acces.php';
$DATA_FILE   = dirname(__DIR__) . '/data/tarifs.json';

$DEFAULT_TARIFS = [
    'seuilJours' => 5,
    'gabarits' => [
        ['id' => 'petit', 'nom' => 'Petit chien', 'description' => "Jusqu'à 10 kg", 'prix' => 22, 'prixLongSejour' => 20],
        ['id' => 'moyen', 'nom' => 'Chien moyen', 'description' => 'De 10 à 25 kg', 'prix' => 26, 'prixLongSejour' => 24],
        ['id' => 'grand', 'nom' => 'Grand chien', 'description' => 'Plus de 25 kg', 'prix' => 30, 'prixLongSejour' => 28],
    ],
];

header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => dirname($_SERVER['SCRIPT_NAME']) ?: '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_name('cdm_admin');
session_start();

// ---------- Outils ----------
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

// Le fichier commence par une ligne PHP qui coupe toute lecture depuis le web ;
// il est lu comme du texte (et non inclus) pour ne jamais être servi depuis le cache OPcache.
const ACCESS_GUARD = "<?php http_response_code(404); exit; ?>\n";

function load_access(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $raw = (string) file_get_contents($file);
    $data = json_decode(substr($raw, strlen(ACCESS_GUARD)), true);
    return is_array($data) && isset($data['user'], $data['hash']) ? $data : null;
}

function save_access(string $file, string $user, string $password): bool
{
    $data = ['user' => $user, 'hash' => password_hash($password, PASSWORD_DEFAULT)];
    return write_atomic($file, ACCESS_GUARD . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
}

function write_atomic(string $file, string $content): bool
{
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function load_tarifs(string $file, array $default): array
{
    if (is_file($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data) && !empty($data['gabarits'])) {
            return $data;
        }
    }
    return $default;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin']) && ($_SESSION['login_time'] ?? 0) > time() - SESSION_HOURS * 3600;
}

function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function to_number(string $value): float
{
    return (float) str_replace(',', '.', trim($value));
}

// ---------- Traitement des formulaires ----------
$access  = load_access($ACCESS_FILE);
$action  = $_POST['action'] ?? '';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok()) {
    $error = 'La page a expiré. Veuillez réessayer.';
    $action = '';
}

// Création de l'accès (première visite uniquement)
if ($action === 'setup' && $access === null) {
    $user = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    if ($user === '' || text_len($user) > 50) {
        $error = "Choisissez un identifiant (50 caractères maximum).";
    } elseif (text_len($pass) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($pass !== $confirm) {
        $error = 'Les deux mots de passe ne sont pas identiques.';
    } elseif (!save_access($ACCESS_FILE, $user, $pass)) {
        $error = "Impossible d'enregistrer l'accès : le dossier « admin » n'est pas modifiable par le serveur. Contactez votre hébergeur.";
    } else {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['login_time'] = time();
        $access = load_access($ACCESS_FILE);
        $success = 'Votre accès est créé. Vous êtes connectée.';
    }
}

// Connexion
if ($action === 'login' && $access !== null) {
    $user = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (hash_equals($access['user'], $user) && password_verify($pass, $access['hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['login_time'] = time();
    } else {
        sleep(1); // ralentit les essais en série
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}

// Déconnexion
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Enregistrement des tarifs
if ($action === 'save' && is_logged_in()) {
    $seuil = filter_var($_POST['seuilJours'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 60]]);
    $ids = (array) ($_POST['id'] ?? []);
    $gabarits = [];
    if ($seuil === false) {
        $error = 'Le nombre de jours doit être un nombre entier entre 1 et 60.';
    } else {
        foreach ($ids as $i => $id) {
            $nom  = trim((string) ($_POST['nom'][$i] ?? ''));
            $desc = trim((string) ($_POST['description'][$i] ?? ''));
            $prix = to_number((string) ($_POST['prix'][$i] ?? ''));
            $long = to_number((string) ($_POST['prixLongSejour'][$i] ?? ''));
            if ($nom === '' || text_len($nom) > 40 || text_len($desc) > 60) {
                $error = 'Chaque gabarit doit avoir un nom (40 caractères max.) et une description courte.';
                break;
            }
            if ($prix <= 0 || $long <= 0 || $prix > 1000 || $long > 1000) {
                $error = "Les prix de « {$nom} » doivent être compris entre 0 et 1000 €.";
                break;
            }
            $gabarits[] = [
                'id' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) $id)) ?: 'gabarit-' . ($i + 1),
                'nom' => $nom,
                'description' => $desc,
                'prix' => round($prix, 2),
                'prixLongSejour' => round($long, 2),
            ];
        }
    }
    if ($error === '' && $gabarits) {
        $data = ['seuilJours' => $seuil, 'gabarits' => $gabarits, 'misAJour' => date('c')];
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        if (write_atomic($DATA_FILE, $json)) {
            $success = '✓ Prix enregistrés ! Ils sont déjà visibles sur le site.';
        } else {
            $error = "Impossible d'enregistrer : le dossier « data » n'est pas modifiable par le serveur. Contactez votre hébergeur.";
        }
    }
}

// Changement de mot de passe
if ($action === 'password' && is_logged_in() && $access !== null) {
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    if (!password_verify($current, $access['hash'])) {
        sleep(1);
        $error = 'Le mot de passe actuel est incorrect.';
    } elseif (text_len($new) < 8) {
        $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($new !== $confirm) {
        $error = 'Les deux nouveaux mots de passe ne sont pas identiques.';
    } elseif (!save_access($ACCESS_FILE, $access['user'], $new)) {
        $error = "Impossible d'enregistrer le nouveau mot de passe (dossier « admin » non modifiable).";
    } else {
        $access = load_access($ACCESS_FILE);
        $success = '✓ Mot de passe modifié.';
    }
}

$view = $access === null ? 'setup' : (is_logged_in() ? 'tarifs' : 'login');
$tarifs = load_tarifs($DATA_FILE, $DEFAULT_TARIFS);
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Administration — Coeur de Meute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin">

  <header class="site-header">
    <div class="container site-header__inner">
      <a href="../index.html" class="brand" aria-label="Retour au site">
        <span>Coeur</span><span class="logo__de">de</span><span>Meute</span>
      </a>
      <div class="admin__header-actions">
        <a href="../pension.html#simulateur" class="btn btn--small btn--outline-dark">Voir le simulateur</a>
        <?php if ($view === 'tarifs'): ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <button type="submit" name="action" value="logout" class="btn btn--small">Se déconnecter</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="section">

<?php if ($view === 'setup' || $view === 'login'): ?>
    <section class="container admin__login">
      <form class="admin__card" method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <div class="center">
          <span class="admin__lock" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11 V8 a4 4 0 0 1 8 0 V11" /></svg>
          </span>
          <p class="eyebrow">Espace administrateur</p>
          <h1 class="title"><?= $view === 'setup' ? 'Créer votre accès' : 'Connexion' ?></h1>
          <?php if ($view === 'setup'): ?>
          <p class="muted">Première visite : choisissez l'identifiant et le mot de passe qui protégeront l'administration.</p>
          <?php endif; ?>
        </div>

        <label class="admin__label" for="user">Identifiant</label>
        <input type="text" id="user" name="user" autocomplete="username" required value="<?= h($_POST['user'] ?? '') ?>">

        <label class="admin__label" for="password">Mot de passe<?= $view === 'setup' ? ' (8 caractères minimum)' : '' ?></label>
        <input type="password" id="password" name="password" autocomplete="<?= $view === 'setup' ? 'new-password' : 'current-password' ?>" required>

        <?php if ($view === 'setup'): ?>
        <label class="admin__label" for="confirm">Confirmez le mot de passe</label>
        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
        <?php endif; ?>

        <button type="submit" name="action" value="<?= $view ?>" class="btn admin__submit">
          <?= $view === 'setup' ? "Créer l'accès" : 'Se connecter' ?>
        </button>
        <p class="admin__status<?= $error ? ' is-error' : '' ?>" role="status"><?= h($error) ?></p>
      </form>
    </section>

<?php else: ?>
    <section class="container admin__wrap">
      <div class="center">
        <p class="eyebrow">Espace administrateur</p>
        <h1 class="page-hero__title">Tarifs de la pension canine</h1>
        <p class="lead narrow-text">Modifiez les prix puis cliquez sur « Enregistrer ». Les nouveaux prix apparaissent immédiatement dans le simulateur du site.</p>
      </div>

      <form class="admin__card" method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

        <h2 class="title title--sm">Règle du tarif long séjour</h2>
        <label class="admin__inline">
          Le tarif long séjour s'applique quand le séjour dépasse
          <input type="number" name="seuilJours" min="1" max="60" step="1" required value="<?= h($tarifs['seuilJours']) ?>">
          jours.
        </label>

        <h2 class="title title--sm">Prix par jour</h2>
        <div class="admin__table-wrap">
          <table class="admin__table">
            <thead>
              <tr>
                <th scope="col">Gabarit</th>
                <th scope="col">Description</th>
                <th scope="col">Prix / jour</th>
                <th scope="col">Prix long séjour / jour</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tarifs['gabarits'] as $i => $g): ?>
              <tr>
                <td>
                  <input type="hidden" name="id[<?= $i ?>]" value="<?= h($g['id']) ?>">
                  <input type="text" name="nom[<?= $i ?>]" maxlength="40" required value="<?= h($g['nom']) ?>" aria-label="Nom du gabarit <?= $i + 1 ?>">
                </td>
                <td><input type="text" name="description[<?= $i ?>]" maxlength="60" value="<?= h($g['description']) ?>" aria-label="Description du gabarit <?= $i + 1 ?>"></td>
                <td><span class="admin__money"><input type="number" name="prix[<?= $i ?>]" min="0.5" max="1000" step="0.5" required value="<?= h($g['prix']) ?>" aria-label="Prix par jour, <?= h($g['nom']) ?>"> €</span></td>
                <td><span class="admin__money"><input type="number" name="prixLongSejour[<?= $i ?>]" min="0.5" max="1000" step="0.5" required value="<?= h($g['prixLongSejour']) ?>" aria-label="Prix long séjour par jour, <?= h($g['nom']) ?>"> €</span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="admin__actions">
          <button type="submit" name="action" value="save" class="btn">Enregistrer les nouveaux prix</button>
          <a href="" class="btn btn--outline-dark">Annuler les modifications</a>
        </div>
        <p class="admin__status<?= $error ? ' is-error' : ($success ? ' is-success' : '') ?>" role="status"><?= h($error ?: $success) ?></p>
        <p class="muted">
          <?= !empty($tarifs['misAJour'])
              ? 'Dernière modification : ' . h(date('d/m/Y à H:i', strtotime($tarifs['misAJour'])))
              : 'Prix par défaut (aucune modification enregistrée pour le moment).' ?>
        </p>
      </form>

      <details class="admin__card admin__password">
        <summary>Changer mon mot de passe</summary>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <label class="admin__label" for="current">Mot de passe actuel</label>
          <input type="password" id="current" name="current" autocomplete="current-password" required>
          <label class="admin__label" for="new">Nouveau mot de passe (8 caractères minimum)</label>
          <input type="password" id="new" name="new" autocomplete="new-password" required>
          <label class="admin__label" for="confirm-new">Confirmez le nouveau mot de passe</label>
          <input type="password" id="confirm-new" name="confirm" autocomplete="new-password" required>
          <div class="admin__actions">
            <button type="submit" name="action" value="password" class="btn">Changer le mot de passe</button>
          </div>
        </form>
      </details>
    </section>
<?php endif; ?>

  </main>
</body>
</html>
