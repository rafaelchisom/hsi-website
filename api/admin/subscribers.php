<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $format = $_GET['format'] ?? 'json';
    $rows = $db->query('SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC')->fetchAll();

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="hsi-subscribers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Email', 'Subscribed At']);
        foreach ($rows as $r) fputcsv($out, [$r['email'], $r['subscribed_at']]);
        fclose($out);
        exit;
    }

    jsonResponse(['success' => true, 'count' => count($rows), 'subscribers' => $rows]);
}

jsonError('Method not allowed', 405);
