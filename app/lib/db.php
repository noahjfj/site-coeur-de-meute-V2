<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/**
 * Connexion à la base. Toutes les requêtes passent par des « requêtes préparées » :
 * ce que tape un visiteur reste toujours du texte, jamais un ordre pour la base (protection « injection SQL »).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Même jeu de caractères et même règle de tri que les tables (évite les erreurs « mix of collations » sur MySQL 8)
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec("SET time_zone = '" . (new DateTimeImmutable())->format('P') . "', sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function audit(?int $userId, string $action, string $detail = ''): void
{
    q('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (?, ?, ?, ?)', [$userId, $action, mb_substr($detail, 0, 255), client_ip()]);
}
