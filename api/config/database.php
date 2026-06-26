<?php
$envPath = __DIR__ . '/../../.env';
$env = file_exists($envPath) ? parse_ini_file($envPath) : [];

define('DB_HOST', $env['DB_HOST'] ?? '');
define('DB_PORT', (int)($env['DB_PORT'] ?? 5432));
define('DB_NAME', $env['DB_NAME'] ?? 'postgres');
define('DB_USER', $env['DB_USER'] ?? 'postgres');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_SSL',  $env['DB_SSL']  ?? 'require');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_SSL
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
