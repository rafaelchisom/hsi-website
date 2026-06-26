<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $blocks = $db->query('SELECT page, block_key, value, updated_at FROM content_blocks ORDER BY page, block_key')->fetchAll();
    $out = [];
    foreach ($blocks as $row) {
        $out[$row['page']][$row['block_key']] = ['value' => $row['value'], 'updated_at' => $row['updated_at']];
    }
    jsonResponse(['success' => true, 'content' => $out]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $page = preg_replace('/[^a-z0-9_-]/', '', strtolower($body['page'] ?? ''));
    $key  = preg_replace('/[^a-z0-9_]/', '', strtolower($body['key'] ?? ''));
    $val  = $body['value'] ?? '';

    if (!$page || !$key) jsonError('page and key are required');

    $stmt = $db->prepare(
        'INSERT INTO content_blocks (page, block_key, value) VALUES (?, ?, ?)
         ON CONFLICT (page, block_key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()'
    );
    $stmt->execute([$page, $key, $val]);
    jsonResponse(['success' => true, 'saved' => "$page.$key"]);
}

jsonError('Method not allowed', 405);
