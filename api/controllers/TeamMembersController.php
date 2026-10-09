<?php

class TeamMembersController
{
    public static function routes(Router $r): void
    {
        $r->get('/team-members', [self::class, 'index']);
        $r->get('/team-members/{id}', [self::class, 'show']);
        $r->post('/team-members', [self::class, 'create']);
        $r->put('/team-members/reorder', [self::class, 'reorder']);
        $r->put('/team-members/{id}', [self::class, 'update']);
        $r->delete('/team-members/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $published = !Auth::isLoggedIn();
        $sql = 'SELECT tm.*, m.filename AS photo_filename FROM team_members tm
                LEFT JOIN media m ON m.id = tm.photo_id';
        if ($published) {
            $sql .= ' WHERE tm.is_published = 1';
        }
        $sql .= ' ORDER BY tm.sort_order, tm.id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function show(array $params): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT tm.*, m.filename AS photo_filename FROM team_members tm
                LEFT JOIN media m ON m.id = tm.photo_id WHERE tm.id = ?');
        $stmt->execute([$params['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::error('Not found', 404);
        }
        Response::json(self::present($row));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['name', 'role']);
        $tier = self::validateTier($data['tier'] ?? null);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO team_members (name, role, tier, bio, linkedin_url, twitter_url, photo_id, sort_order, is_published, is_tbc)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['name'],
            $data['role'],
            $tier,
            $data['bio'] ?? null,
            $data['linkedin_url'] ?? null,
            $data['twitter_url'] ?? null,
            $data['photo_id'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_published'] ?? 1,
            !empty($data['is_tbc']) ? 1 : 0,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'team_member', $id, $data['name']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        if (array_key_exists('tier', $data)) {
            $data['tier'] = self::validateTier($data['tier']);
        }
        if (array_key_exists('is_tbc', $data)) {
            $data['is_tbc'] = !empty($data['is_tbc']) ? 1 : 0;
        }
        $pdo = Database::get();

        $fields = [];
        $values = [];
        foreach (['name', 'role', 'tier', 'bio', 'linkedin_url', 'twitter_url', 'photo_id', 'sort_order', 'is_published', 'is_tbc'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE team_members SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'team_member', (int) $params['id'], $data['name'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT name FROM team_members WHERE id = ?');
        $stmt->execute([$params['id']]);
        $name = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM team_members WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'team_member', (int) $params['id'], $name ?: null);
        Response::json(['ok' => true]);
    }

    public static function reorder(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['order']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE team_members SET sort_order = ? WHERE id = ?');
        foreach ($data['order'] as $i => $id) {
            $stmt->execute([$i, $id]);
        }
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'role' => $row['role'],
            'tier' => $row['tier'] ?? 'core',
            'bio' => $row['bio'],
            'linkedin_url' => $row['linkedin_url'],
            'twitter_url' => $row['twitter_url'],
            'photo_id' => $row['photo_id'] !== null ? (int) $row['photo_id'] : null,
            'photo_url' => $row['photo_filename'] ? Uploads::url($row['photo_filename']) : null,
            'sort_order' => (int) $row['sort_order'],
            'is_published' => (bool) $row['is_published'],
            'is_tbc' => (bool) ($row['is_tbc'] ?? false),
        ];
    }

    private static function validateTier(?string $tier): string
    {
        $validKeys = TeamTiersController::validKeys();
        if (!$tier) {
            $pdo = Database::get();
            $default = $pdo->query('SELECT tier_key FROM team_tiers ORDER BY sort_order, id LIMIT 1')->fetchColumn();
            return $default ?: 'core';
        }
        if (!in_array($tier, $validKeys, true)) {
            Response::error('Invalid tier — must be one of: ' . implode(', ', $validKeys), 422);
        }
        return $tier;
    }
}
