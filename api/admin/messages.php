<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $db->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    jsonResponse(['success' => true, 'messages' => $rows]);
}

if ($method === 'POST') {
    // Mark message as read
    $body = getJsonBody();
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $db->prepare('UPDATE contact_messages SET read_at = NOW() WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonError('Method not allowed', 405);
