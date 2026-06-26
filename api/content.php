<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

$page = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['page'] ?? 'all'));

try {
    $db = getDB();

    if ($page === 'all') {
        // Return everything in one call
        $blocks = $db->query('SELECT page, block_key, value FROM content_blocks')->fetchAll();
        $team   = $db->query('SELECT id, name, role, bio, photo_url, sort_order FROM team_members WHERE published=1 ORDER BY sort_order')->fetchAll();
        $news   = $db->query('SELECT id, title, author, excerpt, image_url, category, created_at FROM news_articles WHERE published=1 ORDER BY sort_order, created_at DESC')->fetchAll();

        $content = [];
        foreach ($blocks as $row) {
            $content[$row['page']][$row['block_key']] = $row['value'];
        }

        jsonResponse(['success' => true, 'content' => $content, 'team' => $team, 'news' => $news]);
    } elseif ($page === 'team') {
        $team = $db->query('SELECT id, name, role, bio, photo_url, sort_order FROM team_members WHERE published=1 ORDER BY sort_order')->fetchAll();
        $stmt = $db->prepare('SELECT block_key, value FROM content_blocks WHERE page = ?');
        $stmt->execute(['team']);
        $content = [];
        foreach ($stmt->fetchAll() as $row) { $content[$row['block_key']] = $row['value']; }
        jsonResponse(['success' => true, 'page' => 'team', 'team' => $team, 'content' => $content]);

    } elseif ($page === 'news') {
        $news = $db->query('SELECT id, title, author, excerpt, body, image_url, category, created_at FROM news_articles WHERE published=1 ORDER BY sort_order, created_at DESC')->fetchAll();
        $stmt = $db->prepare('SELECT block_key, value FROM content_blocks WHERE page = ?');
        $stmt->execute(['news']);
        $content = [];
        foreach ($stmt->fetchAll() as $row) { $content[$row['block_key']] = $row['value']; }
        jsonResponse(['success' => true, 'page' => 'news', 'news' => $news, 'content' => $content]);

    } else {
        $stmt = $db->prepare('SELECT block_key, value FROM content_blocks WHERE page = ?');
        $stmt->execute([$page]);
        $rows = $stmt->fetchAll();

        $content = [];
        foreach ($rows as $row) {
            $content[$row['block_key']] = $row['value'];
        }

        jsonResponse(['success' => true, 'page' => $page, 'content' => $content]);
    }
} catch (PDOException $e) {
    // DB not set up — return empty so React uses defaults
    jsonResponse(['success' => true, 'content' => [], 'team' => [], 'news' => [], 'note' => 'db_unavailable']);
}
