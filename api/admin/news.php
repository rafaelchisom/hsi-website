<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $db->query('SELECT * FROM news_articles ORDER BY sort_order, created_at DESC')->fetchAll();
    jsonResponse(['success' => true, 'articles' => $rows]);
}

if ($method === 'POST') {
    $body      = getJsonBody();
    $id        = (int)($body['id'] ?? 0);
    $title     = trim($body['title'] ?? '');
    $author    = trim($body['author'] ?? '');
    $excerpt   = trim($body['excerpt'] ?? '');
    $bodyText  = trim($body['body'] ?? '');
    $image_url = trim($body['image_url'] ?? '');
    $category  = trim($body['category'] ?? '');
    $sort      = (int)($body['sort_order'] ?? 0);
    $published = isset($body['published']) ? (int)(bool)$body['published'] : 1;

    if (!$title) jsonError('Title is required');

    if ($id) {
        $stmt = $db->prepare('UPDATE news_articles SET title=?,author=?,excerpt=?,body=?,image_url=?,category=?,sort_order=?,published=? WHERE id=?');
        $stmt->execute([$title, $author, $excerpt, $bodyText, $image_url, $category, $sort, $published, $id]);
        jsonResponse(['success' => true, 'action' => 'updated', 'id' => $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO news_articles (title,author,excerpt,body,image_url,category,sort_order,published) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$title, $author, $excerpt, $bodyText, $image_url, $category, $sort, $published]);
        jsonResponse(['success' => true, 'action' => 'created', 'id' => $db->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $db->prepare('DELETE FROM news_articles WHERE id=?')->execute([$id]);
    jsonResponse(['success' => true, 'deleted' => $id]);
}

jsonError('Method not allowed', 405);
