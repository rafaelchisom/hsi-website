<?php

class CountriesController
{
    public static function routes(Router $r): void
    {
        $r->get('/countries', [self::class, 'index']);
        $r->post('/countries', [self::class, 'create']);
        $r->put('/countries/{id}', [self::class, 'update']);
        $r->delete('/countries/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $sql = 'SELECT * FROM countries ORDER BY sort_order, id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['iso_code', 'name', 'programme_name']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO countries (iso_code, name, programme_name, status, sort_order)
            VALUES (?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            // Lowercased — the Africa map SVG's path ids are all lowercase
            // (e.g. id="gh"), and the frontend does a case-sensitive lookup.
            strtolower($data['iso_code']),
            $data['name'],
            $data['programme_name'],
            $data['status'] ?? 'planned',
            $data['sort_order'] ?? 0,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'country', $id, $data['name']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $fields = [];
        $values = [];
        foreach (['iso_code', 'name', 'programme_name', 'status', 'sort_order'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $f === 'iso_code' ? strtolower($data[$f]) : $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE countries SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'country', (int) $params['id'], $data['name'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT name FROM countries WHERE id = ?');
        $stmt->execute([$params['id']]);
        $name = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM countries WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'country', (int) $params['id'], $name ?: null);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'iso_code' => $row['iso_code'],
            'name' => $row['name'],
            'programme_name' => $row['programme_name'],
            'status' => $row['status'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
