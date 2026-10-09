<?php

class NewsletterController
{
    public static function routes(Router $r): void
    {
        $r->post('/newsletter', [self::class, 'create']);
        $r->get('/newsletter', [self::class, 'index']);
        $r->get('/newsletter/export', [self::class, 'export']);
    }

    public static function create(): void
    {
        $data = Validate::jsonBody();
        if (Honeypot::isBot($data)) {
            Response::json(['ok' => true], 201);
        }
        RateLimiter::enforce('newsletter', 5, 60);
        Validate::requireFields($data, ['email']);
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?) ON CONFLICT (email) DO NOTHING')
            ->execute([$data['email']]);
        Response::json(['ok' => true], 201);
    }

    public static function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $rows = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC LIMIT 500')->fetchAll();
        Response::json(array_map([self::class, 'present'], $rows));
    }

    public static function export(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $rows = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC')->fetchAll();
        $csv = "Email,Subscribed At\n";
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row['email']) . '","' . $row['subscribed_at'] . "\"\n";
        }
        Response::csv($csv, 'newsletter-subscribers.csv');
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'subscribed_at' => $row['subscribed_at'],
        ];
    }
}
