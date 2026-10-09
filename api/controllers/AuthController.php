<?php

class AuthController
{
    public static function routes(Router $r): void
    {
        $r->post('/auth/login', [self::class, 'login']);
        $r->post('/auth/logout', [self::class, 'logout']);
        $r->get('/auth/me', [self::class, 'me']);
        $r->put('/auth/me', [self::class, 'updateMe']);
        $r->get('/auth/sessions', [self::class, 'listSessions']);
        $r->delete('/auth/sessions/{id}', [self::class, 'revokeSession']);
        $r->get('/auth/users', [self::class, 'listUsers']);
        $r->post('/auth/users', [self::class, 'createUser']);
        $r->put('/auth/users/{id}', [self::class, 'updateUser']);
        $r->delete('/auth/users/{id}', [self::class, 'deleteUser']);
        $r->post('/auth/2fa/setup', [self::class, 'setup2fa']);
        $r->post('/auth/2fa/verify', [self::class, 'verify2fa']);
        $r->post('/auth/2fa/disable', [self::class, 'disable2fa']);
        $r->post('/auth/2fa/challenge', [self::class, 'challenge2fa']);
    }

    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_WINDOW_MINUTES = 15;

    public static function login(): void
    {
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['email', 'password']);

        $pdo = Database::get();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts
            WHERE email = ? AND succeeded = 0 AND attempted_at > NOW() - CAST(? AS INTEGER) * INTERVAL '1 minute'");
        $stmt->execute([$data['email'], self::LOCKOUT_WINDOW_MINUTES]);
        if ((int) $stmt->fetchColumn() >= self::MAX_FAILED_ATTEMPTS) {
            Response::error('Too many failed login attempts. Try again later.', 429);
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1');
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        $success = $user && password_verify($data['password'], $user['password_hash']);
        $pdo->prepare('INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (?, ?, ?)')
            ->execute([$data['email'], $ip, $success ? 1 : 0]);

        if (!$success) {
            Response::error('Invalid email or password', 401);
        }

        if ($user['totp_enabled']) {
            session_regenerate_id(true);
            $pendingToken = bin2hex(random_bytes(32));
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            $_SESSION['pending_2fa_token'] = $pendingToken;
            $_SESSION['pending_2fa_expires'] = time() + 300;
            Response::json(['requires_2fa' => true, 'pending_token' => $pendingToken]);
        }

        $csrf = Auth::login($user);
        Response::json([
            'user' => self::publicUser($user),
            'csrf_token' => $csrf,
        ]);
    }

    /** Completes a login that was paused for 2FA — accepts either a TOTP code or a one-time recovery code. */
    public static function challenge2fa(): void
    {
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['pending_token']);

        $pendingUserId = $_SESSION['pending_2fa_user_id'] ?? null;
        $pendingToken = $_SESSION['pending_2fa_token'] ?? null;
        $expires = $_SESSION['pending_2fa_expires'] ?? 0;

        if (!$pendingUserId || !$pendingToken || !hash_equals($pendingToken, (string) $data['pending_token']) || time() > $expires) {
            Response::error('2FA challenge expired or invalid — please log in again', 401);
        }

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$pendingUserId]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::error('2FA challenge expired or invalid — please log in again', 401);
        }

        $verified = false;
        if (!empty($data['code'])) {
            $verified = Totp::verify($user['totp_secret'], $data['code']);
        } elseif (!empty($data['recovery_code'])) {
            $verified = self::consumeRecoveryCode($pdo, (int) $user['id'], $data['recovery_code']);
        }

        if (!$verified) {
            Response::error('Invalid code', 401);
        }

        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_token'], $_SESSION['pending_2fa_expires']);
        $csrf = Auth::login($user);
        Response::json([
            'user' => self::publicUser($user),
            'csrf_token' => $csrf,
        ]);
    }

    /** Step 1 of enabling 2FA: generate and store a secret (not yet active until verify2fa confirms it). */
    public static function setup2fa(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT email, totp_enabled FROM users WHERE id = ?');
        $stmt->execute([Auth::currentUserId()]);
        $user = $stmt->fetch();
        if ($user['totp_enabled']) {
            Response::error('2FA is already enabled — disable it first to reconfigure', 422);
        }

        $secret = Totp::generateSecret();
        $pdo->prepare('UPDATE users SET totp_secret = ? WHERE id = ?')->execute([$secret, Auth::currentUserId()]);

        Response::json([
            'secret' => $secret,
            'otpauth_uri' => Totp::provisioningUri($secret, $user['email']),
        ]);
    }

    /** Step 2: confirm the user's authenticator app produces valid codes, then actually turn 2FA on. */
    public static function verify2fa(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['code']);

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT totp_secret FROM users WHERE id = ?');
        $stmt->execute([Auth::currentUserId()]);
        $secret = $stmt->fetchColumn();
        if (!$secret || !Totp::verify($secret, $data['code'])) {
            Response::error('Invalid code', 422);
        }

        $pdo->prepare('UPDATE users SET totp_enabled = 1 WHERE id = ?')->execute([Auth::currentUserId()]);

        // Recovery codes are shown exactly once, here — only the hash is ever stored.
        $pdo->prepare('DELETE FROM user_recovery_codes WHERE user_id = ?')->execute([Auth::currentUserId()]);
        $codes = [];
        $stmt = $pdo->prepare('INSERT INTO user_recovery_codes (user_id, code_hash) VALUES (?, ?)');
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(bin2hex(random_bytes(5)));
            $formatted = substr($code, 0, 5) . '-' . substr($code, 5, 5);
            $codes[] = $formatted;
            $stmt->execute([Auth::currentUserId(), password_hash($formatted, PASSWORD_BCRYPT)]);
        }

        Response::json(['ok' => true, 'recovery_codes' => $codes]);
    }

    public static function disable2fa(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['current_password']);

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([Auth::currentUserId()]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($data['current_password'], $row['password_hash'])) {
            Response::error('Current password is incorrect', 422);
        }

        $pdo->prepare('UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE id = ?')->execute([Auth::currentUserId()]);
        $pdo->prepare('DELETE FROM user_recovery_codes WHERE user_id = ?')->execute([Auth::currentUserId()]);
        Response::json(['ok' => true]);
    }

    private static function consumeRecoveryCode(PDO $pdo, int $userId, string $code): bool
    {
        $stmt = $pdo->prepare('SELECT id, code_hash FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            if (password_verify($code, $row['code_hash'])) {
                $pdo->prepare('UPDATE user_recovery_codes SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
                return true;
            }
        }
        return false;
    }

    public static function logout(): void
    {
        Auth::logout();
        Response::json(['ok' => true]);
    }

    public static function me(): void
    {
        if (!Auth::isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([Auth::currentUserId()]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::error('Unauthorized', 401);
        }
        Response::json(['user' => self::publicUser($user), 'csrf_token' => $_SESSION['csrf_token']]);
    }

    /** Self-service profile update — any logged-in user, not admin-gated. Only touches their own row. */
    public static function updateMe(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();
        $userId = Auth::currentUserId();

        $fields = [];
        $values = [];

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }

        if (!empty($data['password'])) {
            Validate::requireFields($data, ['current_password']);
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($data['current_password'], $row['password_hash'])) {
                Response::error('Current password is incorrect', 422);
            }
            if (strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
                Response::error('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters', 422);
            }
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $passwordChanged = !empty($data['password']);
        $values[] = $userId;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($passwordChanged && !empty($user['email'])) {
            Mailer::send(
                $user['email'],
                'Your DHAF Admin password was changed',
                "Hi {$user['name']},\n\nThis is a confirmation that the password for your DHAF Admin account ({$user['email']}) was just changed.\n\nIf you made this change, no action is needed. If you didn't, contact another admin immediately — someone else may have access to your account."
            );
        }

        Response::json(['user' => self::publicUser($user)]);
    }

    /** Lists the caller's own active (tracked) sessions — never other users'. */
    public static function listSessions(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id, ip_address, user_agent, created_at, last_active_at, session_id
            FROM user_sessions WHERE user_id = ? ORDER BY last_active_at DESC');
        $stmt->execute([Auth::currentUserId()]);
        $currentSessionId = session_id();
        Response::json(array_map(static function (array $row) use ($currentSessionId): array {
            return [
                'id' => (int) $row['id'],
                'ip_address' => $row['ip_address'],
                'user_agent' => $row['user_agent'],
                'created_at' => $row['created_at'],
                'last_active_at' => $row['last_active_at'],
                'is_current' => hash_equals($row['session_id'], $currentSessionId),
            ];
        }, $stmt->fetchAll()));
    }

    /** Revokes one of the caller's own sessions (scoped by user_id so you can never revoke someone else's). */
    public static function revokeSession(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE id = ? AND user_id = ?');
        $stmt->execute([$params['id'], Auth::currentUserId()]);
        Response::json(['ok' => true]);
    }

    public static function listUsers(): void
    {
        Auth::requireRole('admin');
        $pdo = Database::get();
        $rows = $pdo->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY name')->fetchAll();
        Response::json($rows);
    }

    private const MIN_PASSWORD_LENGTH = 8;

    public static function createUser(): void
    {
        Auth::requireRole('admin');
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['name', 'email', 'password', 'role']);
        if (!in_array($data['role'], ['admin', 'editor'], true)) {
            Response::error('role must be admin or editor', 422);
        }
        if (strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            Response::error('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters', 422);
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id');
        $stmt->execute([$data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT), $data['role']]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'user', $id, $data['email']);
        Response::json(['id' => $id], 201);
    }

    public static function updateUser(array $params): void
    {
        Auth::requireRole('admin');
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $targetId = (int) $params['id'];
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $targetEmail = $stmt->fetchColumn();
        $demotingOrDeactivating = (array_key_exists('role', $data) && $data['role'] !== 'admin')
            || (array_key_exists('is_active', $data) && !$data['is_active']);
        if ($demotingOrDeactivating && self::isLastActiveAdmin($pdo, $targetId)) {
            Response::error('Cannot remove admin access from the last active admin account', 422);
        }

        if (array_key_exists('role', $data) && !in_array($data['role'], ['admin', 'editor'], true)) {
            Response::error('role must be admin or editor', 422);
        }
        if (!empty($data['password']) && strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            Response::error('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters', 422);
        }

        $fields = [];
        $values = [];
        foreach (['name', 'email', 'role', 'is_active'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $targetId;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'user', $targetId, $data['email'] ?? ($targetEmail ?: null));
        Response::json(['ok' => true]);
    }

    public static function deleteUser(array $params): void
    {
        Auth::requireRole('admin');
        $pdo = Database::get();
        $targetId = (int) $params['id'];

        if (self::isLastActiveAdmin($pdo, $targetId)) {
            Response::error('Cannot delete the last active admin account', 422);
        }

        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $email = $stmt->fetchColumn();

        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
        ActivityLog::record('delete', 'user', $targetId, $email ?: null);
        Response::json(['ok' => true]);
    }

    /**
     * True if $userId is currently an active admin AND no other active admin exists —
     * i.e. removing/demoting/deleting them would lock everyone out of the CMS.
     */
    private static function isLastActiveAdmin(PDO $pdo, int $userId): bool
    {
        $stmt = $pdo->prepare("SELECT role, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target || $target['role'] !== 'admin' || !$target['is_active']) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() === 0;
    }

    private static function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'totp_enabled' => (bool) ($user['totp_enabled'] ?? false),
        ];
    }
}
