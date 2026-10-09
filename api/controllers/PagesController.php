<?php

class PagesController
{
    public static function routes(Router $r): void
    {
        $r->get('/pages/{slug}', [self::class, 'show']);
        $r->put('/pages/{slug}/sections/{key}', [self::class, 'updateSection']);
        $r->put('/pages/{slug}', [self::class, 'update']);
    }

    public static function show(array $params): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ?');
        $stmt->execute([$params['slug']]);
        $page = $stmt->fetch();
        if (!$page) {
            Response::error('Not found', 404);
        }

        $stmt = $pdo->prepare('SELECT * FROM page_sections WHERE page_id = ? ORDER BY sort_order');
        $stmt->execute([$page['id']]);
        $sections = array_map([self::class, 'presentSection'], $stmt->fetchAll());

        Response::json([
            'page' => self::presentPage($page),
            'sections' => $sections,
        ]);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = ?');
        $stmt->execute([$params['slug']]);
        $page = $stmt->fetch();
        if (!$page) {
            Response::error('Not found', 404);
        }

        $fields = [];
        $values = [];
        foreach (['title', 'meta_description'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $page['id'];
        $pdo->prepare('UPDATE pages SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'page', (int) $page['id'], $params['slug']);
        Response::json(['ok' => true]);
    }

    public static function updateSection(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = ?');
        $stmt->execute([$params['slug']]);
        $page = $stmt->fetch();
        if (!$page) {
            Response::error('Not found', 404);
        }

        // Snapshot the existing section (if any) before overwriting it.
        $stmt = $pdo->prepare('SELECT id FROM page_sections WHERE page_id = ? AND section_key = ?');
        $stmt->execute([$page['id'], $params['key']]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            RevisionsController::snapshot($pdo, 'page_section', (int) $existingId);
        }

        $heading = $data['heading'] ?? null;
        $bodyHtml = Sanitizer::html($data['body_html'] ?? null);
        $dataJson = array_key_exists('data_json', $data) && $data['data_json'] !== null
            ? json_encode($data['data_json'])
            : null;
        $imageId = $data['image_id'] ?? null;
        $sortOrder = $data['sort_order'] ?? 0;

        $stmt = $pdo->prepare('INSERT INTO page_sections (page_id, section_key, sort_order, heading, body_html, data_json, image_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (page_id, section_key) DO UPDATE SET
                sort_order = EXCLUDED.sort_order,
                heading = EXCLUDED.heading,
                body_html = EXCLUDED.body_html,
                data_json = EXCLUDED.data_json,
                image_id = EXCLUDED.image_id');
        $stmt->execute([$page['id'], $params['key'], $sortOrder, $heading, $bodyHtml, $dataJson, $imageId]);
        ActivityLog::record('update', 'page_section', (int) $page['id'], $params['slug'] . ':' . $params['key']);
        Response::json(['ok' => true]);
    }

    private static function presentPage(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'meta_description' => $row['meta_description'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private static function presentSection(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'section_key' => $row['section_key'],
            'sort_order' => (int) $row['sort_order'],
            'heading' => $row['heading'],
            'body_html' => $row['body_html'],
            'data_json' => $row['data_json'] !== null ? json_decode($row['data_json'], true) : null,
            'image_id' => $row['image_id'] !== null ? (int) $row['image_id'] : null,
            'updated_at' => $row['updated_at'],
        ];
    }
}
