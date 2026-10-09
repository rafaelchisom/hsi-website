<?php

/**
 * Revision history for the two content types most at risk of accidental
 * overwrite: news articles and page sections (the latter edited via a raw
 * JSON textarea in the admin). A snapshot is taken of the row's prior state
 * immediately before each update — see NewsController::update() and
 * PagesController::updateSection() for the call sites.
 */
class RevisionsController
{
    private const TABLES = [
        'news_article' => 'news_articles',
        'page_section' => 'page_sections',
    ];

    private const EDITABLE_FIELDS = [
        'news_article' => [
            'title', 'slug', 'category', 'summary', 'lede', 'body_html', 'author',
            'featured_image_id', 'icon_type', 'background_gradient', 'status', 'publish_date',
        ],
        'page_section' => ['heading', 'body_html', 'data_json', 'image_id', 'sort_order'],
    ];

    public static function routes(Router $r): void
    {
        $r->get('/revisions', [self::class, 'index']);
        $r->post('/revisions/{id}/restore', [self::class, 'restore']);
    }

    public static function index(): void
    {
        Auth::requireAuth();
        $type = $_GET['resource_type'] ?? '';
        $resourceId = (int) ($_GET['resource_id'] ?? 0);
        if (!isset(self::TABLES[$type]) || !$resourceId) {
            Response::error('resource_type and resource_id are required', 422);
        }

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT r.id, r.resource_type, r.resource_id, r.user_id, r.created_at, u.name AS user_name
            FROM revisions r LEFT JOIN users u ON u.id = r.user_id
            WHERE r.resource_type = ? AND r.resource_id = ?
            ORDER BY r.created_at DESC');
        $stmt->execute([$type, $resourceId]);

        Response::json(array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'resource_type' => $row['resource_type'],
                'resource_id' => (int) $row['resource_id'],
                'user_name' => $row['user_name'],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll()));
    }

    public static function restore(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();

        $stmt = $pdo->prepare('SELECT * FROM revisions WHERE id = ?');
        $stmt->execute([$params['id']]);
        $revision = $stmt->fetch();
        if (!$revision || !isset(self::TABLES[$revision['resource_type']])) {
            Response::error('Not found', 404);
        }

        $type = $revision['resource_type'];
        $table = self::TABLES[$type];
        $snapshot = json_decode($revision['data_json'], true);

        // Snapshot the CURRENT state first, so restoring is itself undoable.
        self::snapshot($pdo, $type, (int) $revision['resource_id']);

        $fields = [];
        $values = [];
        foreach (self::EDITABLE_FIELDS[$type] as $f) {
            if (!array_key_exists($f, $snapshot)) {
                continue;
            }
            $fields[] = "$f = ?";
            $values[] = ($f === 'data_json' && $snapshot[$f] !== null) ? json_encode($snapshot[$f]) : $snapshot[$f];
        }
        if ($fields) {
            $values[] = $revision['resource_id'];
            $pdo->prepare("UPDATE $table SET " . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        }

        Response::json(['ok' => true]);
    }

    /** Snapshots the current row for ($resourceType, $resourceId) into revisions. No-op if the row doesn't exist yet. */
    public static function snapshot(PDO $pdo, string $resourceType, int $resourceId): void
    {
        if (!isset(self::TABLES[$resourceType])) {
            return;
        }
        $table = self::TABLES[$resourceType];
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$resourceId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        // page_sections.data_json is stored as a JSON string column — decode it
        // so the revision snapshot holds real nested JSON, not a doubly-encoded string.
        if (array_key_exists('data_json', $row) && $row['data_json'] !== null) {
            $row['data_json'] = json_decode($row['data_json'], true);
        }
        $pdo->prepare('INSERT INTO revisions (resource_type, resource_id, data_json, user_id) VALUES (?, ?, ?, ?)')
            ->execute([$resourceType, $resourceId, json_encode($row), Auth::currentUserId()]);
    }
}
