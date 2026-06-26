<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $pdo = getDB();

    $donations = $pdo->query('SELECT COUNT(*) as count, SUM(amount) as total FROM donations')->fetch();
    $contacts  = $pdo->query('SELECT COUNT(*) as count FROM contact_messages')->fetch();
    $subs      = $pdo->query('SELECT COUNT(*) as count FROM newsletter_subscribers')->fetch();

    jsonResponse([
        'success'      => true,
        'donations'    => (int)$donations['count'],
        'total_raised' => (float)$donations['total'],
        'contacts'     => (int)$contacts['count'],
        'subscribers'  => (int)$subs['count'],
    ]);
} catch (PDOException) {
    jsonResponse([
        'success'      => true,
        'donations'    => 0,
        'total_raised' => 0,
        'contacts'     => 0,
        'subscribers'  => 0,
        'note'         => 'demo mode',
    ]);
}
