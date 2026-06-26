<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body  = getJsonBody();

if (!empty($body['_honey'])) {
    jsonResponse([
        'success' => true,
        'message' => "Thank you for subscribing! You'll receive our latest updates soon.",
    ]);
}

$email = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    jsonError('Valid email address is required');
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO newsletter_subscribers (email, subscribed_at)
         VALUES (:email, NOW())'
    );
    $stmt->execute([':email' => $email]);
} catch (PDOException $e) {
    error_log("Database error in newsletter.php: " . $e->getMessage());
    jsonError('A database error occurred. Please try again later.', 500);
}

jsonResponse([
    'success' => true,
    'message' => "Thank you for subscribing! You'll receive our latest updates soon.",
]);
