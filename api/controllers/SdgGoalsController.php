<?php

class SdgGoalsController
{
    public static function routes(Router $r): void
    {
        $r->get('/sdg-goals', [self::class, 'index']);
        $r->post('/sdg-goals', [self::class, 'create']);
        $r->put('/sdg-goals/{id}', [self::class, 'update']);
        $r->delete('/sdg-goals/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $sql = 'SELECT * FROM sdg_goals ORDER BY sort_order, id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['sdg_number', 'title']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO sdg_goals (sdg_number, target_label, alignment_type, title, description, icon, color_class, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['sdg_number'],
            $data['target_label'] ?? null,
            $data['alignment_type'] ?? 'supporting',
            $data['title'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            $data['color_class'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'sdg_goal', $id, $data['title']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $fields = [];
        $values = [];
        foreach (['sdg_number', 'target_label', 'alignment_type', 'title', 'description', 'icon', 'color_class', 'sort_order'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE sdg_goals SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'sdg_goal', (int) $params['id'], $data['title'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT title FROM sdg_goals WHERE id = ?');
        $stmt->execute([$params['id']]);
        $title = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM sdg_goals WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'sdg_goal', (int) $params['id'], $title ?: null);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'sdg_number' => (int) $row['sdg_number'],
            'target_label' => $row['target_label'],
            'alignment_type' => $row['alignment_type'],
            'title' => $row['title'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'color_class' => $row['color_class'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
