<?php

class NewsController
{
    public static function routes(Router $r): void
    {
        $r->get('/news/admin', [self::class, 'adminIndex']);
        $r->get('/news/admin/{id}', [self::class, 'adminShow']);
        $r->get('/news', [self::class, 'index']);
        $r->get('/news/{slug}', [self::class, 'show']);
        $r->post('/news', [self::class, 'create']);
        $r->put('/news/{id}', [self::class, 'update']);
        $r->delete('/news/{id}', [self::class, 'delete']);
    }

    private const DEFAULT_PER_PAGE = 12;
    private const MAX_PER_PAGE = 50;

    public static function index(): void
    {
        $pdo = Database::get();
        // A 'scheduled' article becomes publicly visible once its publish_date
        // has passed — computed at read time (no cron job in this stack).
        $from = " FROM news_articles n
                LEFT JOIN media m ON m.id = n.featured_image_id
                WHERE (n.status = 'published' OR (n.status = 'scheduled' AND n.publish_date <= CURRENT_DATE))";
        $values = [];
        if (!empty($_GET['category'])) {
            $from .= ' AND n.category = ?';
            $values[] = $_GET['category'];
        }
        if (!empty($_GET['q'])) {
            $from .= ' AND (n.title ILIKE ? OR n.summary ILIKE ?)';
            $like = '%' . $_GET['q'] . '%';
            $values[] = $like;
            $values[] = $like;
        }
        $sql = 'SELECT n.*, m.filename AS featured_image_filename' . $from
            . ' ORDER BY n.publish_date DESC NULLS LAST, n.id DESC';

        [$page, $perPage] = self::pagination(self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $rows = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT COUNT(*)' . $from);
        $stmt->execute($values);
        $total = (int) $stmt->fetchColumn();

        Response::json([
            'data' => array_map([self::class, 'present'], $rows),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
    }

    /**
     * Reads and clamps ?page= / ?per_page= query params.
     * @return array{0:int,1:int} [page, perPage]
     */
    private static function pagination(int $default, int $max): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? $default);
        if ($perPage <= 0) {
            $perPage = $default;
        }
        $perPage = min($perPage, $max);
        return [$page, $perPage];
    }

    public static function show(array $params): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT n.*, m.filename AS featured_image_filename FROM news_articles n
                LEFT JOIN media m ON m.id = n.featured_image_id WHERE n.slug = ?');
        $stmt->execute([$params['slug']]);
        $row = $stmt->fetch();
        $isPubliclyVisible = $row && (
            $row['status'] === 'published'
            || ($row['status'] === 'scheduled' && $row['publish_date'] !== null && $row['publish_date'] <= date('Y-m-d'))
        );
        if (!$row || (!$isPubliclyVisible && !Auth::isLoggedIn())) {
            Response::error('Not found', 404);
        }
        Response::json(self::present($row));
    }

    public static function adminIndex(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $sql = 'SELECT n.*, m.filename AS featured_image_filename FROM news_articles n
                LEFT JOIN media m ON m.id = n.featured_image_id
                ORDER BY n.created_at DESC';
        Response::json(array_map([self::class, 'present'], $pdo->query($sql)->fetchAll()));
    }

    public static function adminShow(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT n.*, m.filename AS featured_image_filename FROM news_articles n
                LEFT JOIN media m ON m.id = n.featured_image_id WHERE n.id = ?');
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
        Validate::requireFields($data, ['title', 'slug', 'category']);
        if (($data['status'] ?? 'draft') === 'scheduled' && empty($data['publish_date'])) {
            Response::error('publish_date is required when status is "scheduled"', 422);
        }
        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO news_articles
            (title, slug, category, summary, lede, body_html, author, featured_image_id, icon_type, background_gradient, status, publish_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['category'],
            $data['summary'] ?? null,
            $data['lede'] ?? null,
            Sanitizer::html($data['body_html'] ?? null),
            $data['author'] ?? 'Digital Healthcare Access Foundation',
            $data['featured_image_id'] ?? null,
            $data['icon_type'] ?? null,
            $data['background_gradient'] ?? null,
            $data['status'] ?? 'draft',
            $data['publish_date'] ?? null,
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'news_article', $id, $data['title']);
        Response::json(['id' => $id], 201);
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        $data = Validate::jsonBody();
        $pdo = Database::get();

        if (array_key_exists('status', $data) && $data['status'] === 'scheduled') {
            $publishDate = $data['publish_date'] ?? null;
            if ($publishDate === null) {
                $stmt = $pdo->prepare('SELECT publish_date FROM news_articles WHERE id = ?');
                $stmt->execute([$params['id']]);
                $publishDate = $stmt->fetchColumn();
            }
            if (empty($publishDate)) {
                Response::error('publish_date is required when status is "scheduled"', 422);
            }
        }

        RevisionsController::snapshot($pdo, 'news_article', (int) $params['id']);

        $fields = [];
        $values = [];
        foreach ([
            'title', 'slug', 'category', 'summary', 'lede', 'body_html', 'author',
            'featured_image_id', 'icon_type', 'background_gradient', 'status', 'publish_date',
        ] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $f === 'body_html' ? Sanitizer::html($data[$f]) : $data[$f];
            }
        }
        if (!$fields) {
            Response::error('No fields to update', 422);
        }
        $values[] = $params['id'];
        $pdo->prepare('UPDATE news_articles SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
        ActivityLog::record('update', 'news_article', (int) $params['id'], $data['title'] ?? null);
        Response::json(['ok' => true]);
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT title FROM news_articles WHERE id = ?');
        $stmt->execute([$params['id']]);
        $title = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM news_articles WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'news_article', (int) $params['id'], $title ?: null);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'category' => $row['category'],
            'summary' => $row['summary'],
            'lede' => $row['lede'],
            'body_html' => $row['body_html'],
            'author' => $row['author'],
            'featured_image_id' => $row['featured_image_id'] !== null ? (int) $row['featured_image_id'] : null,
            'featured_image_url' => $row['featured_image_filename'] ? Uploads::url($row['featured_image_filename']) : null,
            'icon_type' => $row['icon_type'],
            'background_gradient' => $row['background_gradient'],
            'status' => $row['status'],
            'publish_date' => $row['publish_date'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
