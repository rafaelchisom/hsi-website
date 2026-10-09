<?php

class SiteSettingsController
{
    // These two values get interpolated into a script/meta tag on every public
    // page (see app-public's Layout.jsx) — restrict to a safe token shape so a
    // malicious value can never break out of that context, defense in depth
    // alongside the frontend building the tags itself rather than rendering
    // raw admin-supplied markup.
    private const VALUE_PATTERNS = [
        'google_analytics_id' => ['/^[A-Za-z0-9_-]{1,50}$/', 'letters, numbers, underscore and hyphen only'],
        'google_site_verification' => ['/^[A-Za-z0-9_-]{1,100}$/', 'letters, numbers, underscore and hyphen only'],
        'donate_min_amount' => ['/^\d{1,7}(\.\d{1,2})?$/', 'a plain number, e.g. 5 or 5.00'],
        'donate_max_amount' => ['/^\d{1,7}(\.\d{1,2})?$/', 'a plain number, e.g. 100000'],
        'map_max_width' => ['/^([3-9]\d{2}|1[0-3]\d{2}|1400)$/', 'a whole number of pixels between 300 and 1400'],
        'smtp_port' => ['/^\d{1,5}$/', 'a port number, e.g. 587'],
        'smtp_encryption' => ['/^(tls|ssl|none)$/', 'one of: tls, ssl, none'],
        'paypal_mode' => ['/^(sandbox|live)$/', 'one of: sandbox, live'],
        'nav_style' => ['/^(light|navy)$/', 'one of: light, navy'],
    ];

    public static function routes(Router $r): void
    {
        $r->get('/site-settings', [self::class, 'index']);
        $r->put('/site-settings', [self::class, 'update']);
        $r->post('/site-settings/test-email', [self::class, 'testEmail']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        $isAdmin = Auth::isLoggedIn();
        $out = [];
        foreach ($rows as $row) {
            // Keys ending in _secret hold values (e.g. paypal_secret) that must
            // never reach the unauthenticated public site — this endpoint also
            // backs the public homepage, so exclude them unless an admin is
            // viewing (the admin Settings form needs to know one is already set).
            if (!$isAdmin && str_ends_with($row['setting_key'], '_secret')) {
                continue;
            }
            $out[$row['setting_key']] = $row['setting_value'];
        }
        Response::json((object) $out);
    }

    public static function update(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        foreach ($data as $key => $value) {
            if (!preg_match('/^[a-z0-9_]{1,100}$/', (string) $key)) {
                Response::error("Invalid setting key: $key (lowercase letters, numbers, underscore only)", 422);
            }
            if (isset(self::VALUE_PATTERNS[$key]) && $value !== '' && $value !== null) {
                [$pattern, $hint] = self::VALUE_PATTERNS[$key];
                if (!preg_match($pattern, (string) $value)) {
                    Response::error("Invalid value for $key — expected $hint", 422);
                }
            }
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
            ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value');
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        $count = count($data);
        $keysLabel = implode(', ', array_keys($data));
        if (strlen($keysLabel) > 250) {
            $keysLabel = substr($keysLabel, 0, 247) . '...';
        }
        ActivityLog::record('update', 'site_setting', null, "updated $count setting" . ($count === 1 ? '' : 's') . ': ' . $keysLabel);
        Response::json(['ok' => true]);
    }

    /**
     * Sends a real test email using whatever mail configuration is currently
     * saved (SMTP if set, otherwise mail()) — lets an admin confirm email
     * actually works right after deploying to a new server, instead of
     * waiting for a real contact-form submission to find out it doesn't.
     */
    public static function testEmail(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $to = trim((string) ($data['to'] ?? ''));
        if ($to === '') {
            $pdo = Database::get();
            $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
            $stmt->execute([Auth::currentUserId()]);
            $to = (string) $stmt->fetchColumn();
        }
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Response::error('No valid recipient address', 422);
        }

        $result = Mailer::attempt(
            $to,
            'DHAF Admin — test email',
            "This is a test email from your Digital Healthcare Access Foundation site's admin panel.\n\nIf you received this, outgoing email is configured correctly."
        );

        if ($result['ok']) {
            ActivityLog::record('update', 'site_setting', null, "sent test email to $to");
            Response::json(['ok' => true, 'to' => $to]);
        } else {
            Response::json(['ok' => false, 'to' => $to, 'error' => $result['error']], 200);
        }
    }
}
