<?php

class Auth
{
    private static ?bool $sessionValidCache = null;

    public static function init(): void
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => $isHttps,
            'samesite' => 'Lax',
        ]);
        session_name('syncura_session');
        session_start();
    }

    public static function login(array $user): string
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_token'], $_SESSION['pending_2fa_expires']);
        self::trackSession((int) $user['id']);
        return $_SESSION['csrf_token'];
    }

    /** Records/refreshes this browser session in user_sessions for the "active sessions" list. */
    public static function trackSession(int $userId): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
            ON CONFLICT (session_id) DO UPDATE SET user_id = EXCLUDED.user_id, last_active_at = CURRENT_TIMESTAMP');
        $stmt->execute([
            session_id(),
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
        self::$sessionValidCache = true;
    }

    public static function logout(): void
    {
        $sid = session_id();
        $_SESSION = [];
        session_destroy();
        if ($sid) {
            Database::get()->prepare('DELETE FROM user_sessions WHERE session_id = ?')->execute([$sid]);
        }
    }

    public static function currentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function currentRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        return self::sessionIsTracked();
    }

    /**
     * Confirms the current session hasn't been revoked (its row deleted from
     * user_sessions by the owner via another browser/device) and refreshes
     * last_active_at. Memoized per-request so it only runs once regardless of
     * how many times isLoggedIn()/requireAuth() are called in one request.
     */
    private static function sessionIsTracked(): bool
    {
        if (self::$sessionValidCache !== null) {
            return self::$sessionValidCache;
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT 1 FROM user_sessions WHERE session_id = ?');
        $stmt->execute([session_id()]);
        $exists = (bool) $stmt->fetchColumn();
        if ($exists) {
            $pdo->prepare('UPDATE user_sessions SET last_active_at = CURRENT_TIMESTAMP WHERE session_id = ?')
                ->execute([session_id()]);
        }
        self::$sessionValidCache = $exists;
        return $exists;
    }

    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            self::requireCsrf();
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if (self::currentRole() !== $role) {
            Response::error('Forbidden', 403);
        }
    }

    private static function requireCsrf(): void
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if ($expected === '' || !hash_equals($expected, $header)) {
            Response::error('Invalid CSRF token', 403);
        }
    }
}
