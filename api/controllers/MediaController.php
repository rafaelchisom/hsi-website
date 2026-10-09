<?php

class MediaController
{
    // SVG deliberately excluded — it can carry embedded <script>. Site-chrome
    // SVGs (logo, map) stay static files outside this upload pipeline.
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_DIMENSION = 1920;

    private const EXT_FOR_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public static function routes(Router $r): void
    {
        $r->post('/media', [self::class, 'create']);
        $r->get('/media', [self::class, 'index']);
        $r->delete('/media/{id}', [self::class, 'delete']);
    }

    public static function create(): void
    {
        Auth::requireAuth();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded', 422);
        }
        $file = $_FILES['file'];

        $mime = @mime_content_type($file['tmp_name']) ?: $file['type'];
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            Response::error('Unsupported file type', 422);
        }

        // Extension is derived from the verified MIME type, never from the
        // client-supplied filename — keeps it trustworthy and guarantees it
        // matches the uploads/.htaccess extension whitelist.
        $ext = self::EXT_FOR_MIME[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = Uploads::LOCAL_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::error('Failed to save file', 500);
        }

        [$width, $height] = self::downscaleAndRecompress($dest, $mime);
        $sizeBytes = filesize($dest) ?: $file['size'];

        $storageError = Uploads::publish($filename, $mime);
        if ($storageError !== null) {
            error_log('[Media] ' . $storageError);
            @unlink($dest);
            Response::error('Failed to save file', 500);
        }

        $pdo = Database::get();
        $stmt = $pdo->prepare('INSERT INTO media (filename, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
        $stmt->execute([
            $filename,
            $file['name'],
            $mime,
            $sizeBytes,
            $width,
            $height,
            $_POST['alt_text'] ?? null,
            Auth::currentUserId(),
        ]);
        $id = (int) $stmt->fetchColumn();
        ActivityLog::record('create', 'media', $id, $file['name']);

        Response::json([
            'id' => $id,
            'url' => Uploads::url($filename),
        ], 201);
    }

    /**
     * Downscales images wider/taller than MAX_DIMENSION (preserving aspect
     * ratio) and re-encodes at a sane quality, overwriting the file in place.
     * Returns the final [width, height], whether or not it was resized.
     */
    private static function downscaleAndRecompress(string $path, string $mime): array
    {
        $info = @getimagesize($path);
        if (!$info) {
            return [null, null];
        }
        [$origWidth, $origHeight] = $info;

        switch ($mime) {
            case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
            case 'image/png': $src = @imagecreatefrompng($path); break;
            case 'image/gif': $src = @imagecreatefromgif($path); break;
            case 'image/webp': $src = @imagecreatefromwebp($path); break;
            default: $src = null;
        }
        if (!$src) {
            return [$origWidth, $origHeight];
        }

        $scale = min(1, self::MAX_DIMENSION / max($origWidth, $origHeight));
        $newWidth = max(1, (int) round($origWidth * $scale));
        $newHeight = max(1, (int) round($origHeight * $scale));

        if ($scale < 1) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($src);
            $src = $resized;
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        switch ($mime) {
            case 'image/jpeg': imagejpeg($src, $path, 82); break;
            case 'image/png': imagepng($src, $path, 6); break;
            case 'image/gif': imagegif($src, $path); break;
            case 'image/webp': imagewebp($src, $path, 82); break;
        }
        imagedestroy($src);

        return [$newWidth, $newHeight];
    }

    private const DEFAULT_PER_PAGE = 24;
    private const MAX_PER_PAGE = 100;

    public static function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();

        [$page, $perPage] = self::pagination(self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        $sql = 'SELECT * FROM media ORDER BY created_at DESC, id DESC LIMIT '
            . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
        $rows = $pdo->query($sql)->fetchAll();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM media')->fetchColumn();

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

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT filename, original_name FROM media WHERE id = ?');
        $stmt->execute([$params['id']]);
        $row = $stmt->fetch();
        if ($row) {
            Uploads::delete($row['filename']);
        }
        $pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$params['id']]);
        ActivityLog::record('delete', 'media', (int) $params['id'], $row['original_name'] ?? null);
        Response::json(['ok' => true]);
    }

    private static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'filename' => $row['filename'],
            'original_name' => $row['original_name'],
            'mime_type' => $row['mime_type'],
            'size_bytes' => (int) $row['size_bytes'],
            'width' => $row['width'] !== null ? (int) $row['width'] : null,
            'height' => $row['height'] !== null ? (int) $row['height'] : null,
            'alt_text' => $row['alt_text'],
            'uploaded_by' => $row['uploaded_by'] !== null ? (int) $row['uploaded_by'] : null,
            'url' => Uploads::url($row['filename']),
            'created_at' => $row['created_at'],
        ];
    }
}
