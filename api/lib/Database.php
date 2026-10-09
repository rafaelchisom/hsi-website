<?php

/**
 * PostgreSQL (Supabase) connection. Emulated prepares keep every statement a
 * single round trip with no server-side prepared statements, which is what
 * Supabase's transaction pooler (port 6543) requires.
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
                    PDO::ATTR_STATEMENT_CLASS => [DatabaseStatement::class, []],
                ]
            );
        }
        return self::$pdo;
    }
}

/**
 * The flag columns are SMALLINT 0/1 (MySQL TINYINT(1) in the original schema).
 * MySQL silently accepted PHP booleans bound for them — the admin sends JSON
 * true/false for is_active, is_published, is_featured — but PDO binds false as
 * '' which PostgreSQL rejects, so booleans are bound as 1/0 here instead.
 */
class DatabaseStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        if ($params !== null) {
            foreach ($params as $key => $value) {
                if (is_bool($value)) {
                    $params[$key] = $value ? 1 : 0;
                }
            }
        }
        return parent::execute($params);
    }
}
