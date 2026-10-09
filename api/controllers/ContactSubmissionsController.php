<?php

class ContactSubmissionsController
{
    public static function routes(Router $r): void
    {
        $r->post('/contact-submissions', [self::class, 'create']);
        $r->get('/contact-submissions', [self::class, 'index']);
        $r->get('/contact-submissions/export', [self::class, 'export']);
        $r->put('/contact-submissions/{id}', [self::class, 'update']);
    }

    public static function create(): void
    {
        $data = Validate::jsonBody();
        if (Honeypot::isBot($data)) {
            Response::json(['ok' => true, 'id' => 0], 201);
        }
        RateLimiter::enforce('contact', 5, 60);
        Validate::requireFields($data, ['full_name', 'email', 'enquiry_type', 'message']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO contact_submissions (full_name, email, organisation, enquiry_type, message)
            VALUES (?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['organisation'] ?? null,
            $data['enquiry_type'],
            $data['message'],
        ]);
        $id = (int) $stmt->fetchColumn();

        $notifyEmail = SiteSettings::get('notify_email', getenv('NOTIFY_EMAIL') ?: null);
        if ($notifyEmail) {
            Mailer::send(
                $notifyEmail,
                'New contact form submission: ' . $data['enquiry_type'],
                "From: {$data['full_name']} <{$data['email']}>\n\n{$data['message']}"
            );
        }

        Response::json(['ok' => true, 'id' => $id], 201);
    }

    public static function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $rows = $pdo->query('SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 500')->fetchAll();
        Response::json(array_map([self::class, 'present'], $rows));
    }

    public static function export(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $rows = $pdo->query('SELECT * FROM contact_submissions ORDER BY created_at DESC')->fetchAll();
        $csv = "Name,Email,Organisation,Enquiry Type,Message,Read,Submitted At\n";
        foreach ($rows as $row) {
            $csv .= self::csvRow([
                $row['full_name'], $row['email'], $row['organisation'] ?? '',
                $row['enquiry_type'], $row['message'], $row['is_read'] ? 'Yes' : 'No', $row['created_at'],
            ]);
        }
        Response::csv($csv, 'contact-submissions.csv');
    }

    private static function csvRow(array $fields): string
    {
        return implode(',', array_map(static function ($f) {
            return '"' . str_replace('"', '""', (string) $f) . '"';
        }, $fields)) . "\n";
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        if (!array_key_exists('is_read', $data)) {
            Response::error('No fields to update', 422);
        }
        $pdo = Database::get();
        $pdo->prepare('UPDATE contact_submissions SET is_read = ? WHERE id = ?')
            ->execute([$data['is_read'] ? 1 : 0, $params['id']]);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'organisation' => $row['organisation'],
            'enquiry_type' => $row['enquiry_type'],
            'message' => $row['message'],
            'is_read' => (bool) $row['is_read'],
            'created_at' => $row['created_at'],
        ];
    }
}
