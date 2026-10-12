<?php

/**
 * PostgreSQL (Supabase) connection. Emulated prepares keep every statement a
 * single round trip with no server-side prepared statements, which is what
 * Supabase's transaction pooler (port 6543) requires.
 *
 * Web requests reuse a persistent connection per Apache worker: opening a new
 * TLS connection to the pooler costs several network round trips (about a
 * second between Render and Supabase), which made every API call slow. The
 * web code never leaves a transaction open, so reusing connections is safe;
 * the Dockerfile caps Apache workers to bound the number of connections.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function dsn(): string
    {
        return 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSL;
    }

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                self::dsn(),
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_PERSISTENT => PHP_SAPI !== 'cli',
                ]
            );
        }
        return self::$pdo;
    }
}

