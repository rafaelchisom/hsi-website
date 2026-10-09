<?php

class DonationTiersController
{
    public static function routes(Router $r): void
    {
        $r->get('/donation-tiers', [self::class, 'index']);
        $r->post('/donation-tiers', [self::class, 'create']);
        $r->put('/donation-tiers/reorder', [self::class, 'reorder']);
        $r->put('/donation-tiers/{id}', [self::class, 'update']);
        $r->delete('/donation-tiers/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $sql = 'SELECT * FROM donation_tiers ORDER BY sort_order, id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['tier_name']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO donation_tiers (tier_name, usd_amount, ngn_amount, description, is_featured, button_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['tier_name'],
            $data['usd_amount'] ?? null,
            $data['ngn_amount'] ?? null,
            $data['description'] ?? null,
            $data['is_featured'] ?? 0,
            $data['button_url'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'donation_tier', $id, $data['tier_name']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $fields = [];
        $values = [];
        foreach (['tier_name', 'usd_amount', 'ngn_amount', 'description', 'is_featured', 'button_url', 'sort_order'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE donation_tiers SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'donation_tier', (int) $params['id'], $data['tier_name'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT tier_name FROM donation_tiers WHERE id = ?');
        $stmt->execute([$params['id']]);
        $name = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM donation_tiers WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'donation_tier', (int) $params['id'], $name ?: null);
        Response::json(['ok' => true]);
    }

    public static function reorder(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['order']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE donation_tiers SET sort_order = ? WHERE id = ?');
        foreach ($data['order'] as $i => $id) {
            $stmt->execute([$i, $id]);
        }
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'tier_name' => $row['tier_name'],
            'usd_amount' => $row['usd_amount'],
            'ngn_amount' => $row['ngn_amount'],
            'description' => $row['description'],
            'is_featured' => (bool) $row['is_featured'],
            'button_url' => $row['button_url'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
