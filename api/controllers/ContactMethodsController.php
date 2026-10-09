<?php

class ContactMethodsController
{
    public static function routes(Router $r): void
    {
        $r->get('/contact-methods', [self::class, 'index']);
        $r->post('/contact-methods', [self::class, 'create']);
        $r->put('/contact-methods/{id}', [self::class, 'update']);
        $r->delete('/contact-methods/{id}', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $pdo = Database::get();
        $sql = 'SELECT * FROM contact_methods ORDER BY sort_order, id';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function create(): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['label', 'icon_type', 'contact_value']);
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO contact_methods (label, icon_type, contact_value, link_type, sort_order)
            VALUES (?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['label'],
            $data['icon_type'],
            $data['contact_value'],
            $data['link_type'] ?? 'mailto',
            $data['sort_order'] ?? 0,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'contact_method', $id, $data['label']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $fields = [];
        $values = [];
        foreach (['label', 'icon_type', 'contact_value', 'link_type', 'sort_order'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE contact_methods SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'contact_method', (int) $params['id'], $data['label'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT label FROM contact_methods WHERE id = ?');
        $stmt->execute([$params['id']]);
        $label = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM contact_methods WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'contact_method', (int) $params['id'], $label ?: null);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'label' => $row['label'],
            'icon_type' => $row['icon_type'],
            'contact_value' => $row['contact_value'],
            'link_type' => $row['link_type'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
