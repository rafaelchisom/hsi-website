<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $db->query('SELECT * FROM team_members ORDER BY sort_order, id')->fetchAll();
    jsonResponse(['success' => true, 'team' => $rows]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $id   = (int)($body['id'] ?? 0);

    $name      = trim($body['name'] ?? '');
    $role      = trim($body['role'] ?? '');
    $bio       = trim($body['bio'] ?? '');
    $photo_url = trim($body['photo_url'] ?? '');
    $sort      = (int)($body['sort_order'] ?? 0);
    $published = isset($body['published']) ? (int)(bool)$body['published'] : 1;

    if (!$name) jsonError('Name is required');

    if ($id) {
        $stmt = $db->prepare('UPDATE team_members SET name=?, role=?, bio=?, photo_url=?, sort_order=?, published=? WHERE id=?');
        $stmt->execute([$name, $role, $bio, $photo_url, $sort, $published, $id]);
        jsonResponse(['success' => true, 'action' => 'updated', 'id' => $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO team_members (name, role, bio, photo_url, sort_order, published) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, $role, $bio, $photo_url, $sort, $published]);
        jsonResponse(['success' => true, 'action' => 'created', 'id' => $db->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $db->prepare('DELETE FROM team_members WHERE id=?')->execute([$id]);
    jsonResponse(['success' => true, 'deleted' => $id]);
}

jsonError('Method not allowed', 405);
