<?php

/**
 * The named groups the Our Team page is organised into (Board of Trustees,
 * Core Team, Clinical Advisory Board, ...). team_members.tier references
 * team_tiers.tier_key by value (no DB foreign key, since tier_key is
 * admin-editable) — update() cascades a tier_key rename onto every member
 * currently in that group so none are silently orphaned.
 */
class TeamTiersController
{
    public static function routes(Router $r): void
    {
        $r->get('/team-tiers', [self::class, 'index']);
        $r->post('/team-tiers', [self::class, 'create']);
        $r->put('/team-tiers/reorder', [self::class, 'reorder']);
        $r->put('/team-tiers/{id}', [self::class, 'update']);
        $r->delete('/team-tiers/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $sql = 'SELECT * FROM team_tiers';
        if (!Auth::isLoggedIn()) {
            $sql .= ' WHERE is_published = 1';
        }
        $sql .= ' ORDER BY sort_order, id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['tier_key', 'title']);
        $key = self::validateKeyFormat($data['tier_key']);
        $pdo = Database::get();

        $exists = $pdo->prepare('SELECT 1 FROM team_tiers WHERE tier_key = ?');
        $exists->execute([$key]);
        if ($exists->fetchColumn()) {
            Response::error("A section with key \"$key\" already exists", 422);
        }

        $stmt = $pdo->prepare('INSERT INTO team_tiers (tier_key, label, title, intro, sort_order, is_published) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $key,
            $data['label'] ?? null,
            $data['title'],
            $data['intro'] ?? null,
            $data['sort_order'] ?? 0,
            array_key_exists('is_published', $data) ? (!empty($data['is_published']) ? 1 : 0) : 1,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'team_tier', $id, $data['title']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $stmt = $pdo->prepare('SELECT * FROM team_tiers WHERE id = ?');
        $stmt->execute([$params['id']]);
        $current = $stmt->fetch();
        if (!$current) {
            Response::error('Not found', 404);
        }

        $newKey = null;
        if (array_key_exists('tier_key', $data)) {
            $newKey = self::validateKeyFormat($data['tier_key']);
            if ($newKey !== $current['tier_key']) {
                $exists = $pdo->prepare('SELECT 1 FROM team_tiers WHERE tier_key = ? AND id != ?');
                $exists->execute([$newKey, $params['id']]);
                if ($exists->fetchColumn()) {
                    Response::error("A section with key \"$newKey\" already exists", 422);
                }
            }
            $data['tier_key'] = $newKey;
        }
        if (array_key_exists('is_published', $data)) {
            $data['is_published'] = !empty($data['is_published']) ? 1 : 0;
        }

        $fields = [];
        $values = [];
        foreach (['tier_key', 'label', 'title', 'intro', 'sort_order', 'is_published'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE team_tiers SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);

        // Keep every member's tier pointing at a real section — if the key
        // changed, move them over rather than leaving them orphaned.
        if ($newKey !== null && $newKey !== $current['tier_key']) {
            $pdo->prepare('UPDATE team_members SET tier = ? WHERE tier = ?')->execute([$newKey, $current['tier_key']]);
        }

        ActivityLog::record('update', 'team_tier', (int) $params['id'], $data['title'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT tier_key, title FROM team_tiers WHERE id = ?');
        $stmt->execute([$params['id']]);
        $tier = $stmt->fetch();
        if (!$tier) {
            Response::error('Not found', 404);
        }

        $count = $pdo->prepare('SELECT COUNT(*) FROM team_members WHERE tier = ?');
        $count->execute([$tier['tier_key']]);
        $memberCount = (int) $count->fetchColumn();
        if ($memberCount > 0) {
            Response::error("Cannot delete — $memberCount team member(s) are still in this section. Move or delete them first.", 409);
        }

        $pdo->prepare('DELETE FROM team_tiers WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'team_tier', (int) $params['id'], $tier['title']);
        Response::json(['ok' => true]);
    }

    public static function reorder(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['order']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE team_tiers SET sort_order = ? WHERE id = ?');
        foreach ($data['order'] as $i => $id) {
            $stmt->execute([$i, $id]);
        }
        Response::json(['ok' => true]);
    }

    /** Every tier_key currently defined — used by TeamMembersController to validate tier assignment. */
    public static function validKeys(): array
    {
        $pdo = Database::get();
        return $pdo->query('SELECT tier_key FROM team_tiers')->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function validateKeyFormat(string $key): string
    {
        $key = trim($key);
        if (!preg_match('/^[a-z0-9_]{1,30}$/', $key)) {
            Response::error('Key must be lowercase letters, numbers, and underscores only (max 30 characters)', 422);
        }
        return $key;
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'tier_key' => $row['tier_key'],
            'label' => $row['label'],
            'title' => $row['title'],
            'intro' => $row['intro'],
            'sort_order' => (int) $row['sort_order'],
            'is_published' => (bool) $row['is_published'],
        ];
    }
}
