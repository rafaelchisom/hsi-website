<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();

$amount    = filter_var($body['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$frequency = in_array($body['frequency'] ?? '', ['one-time', 'monthly']) ? $body['frequency'] : 'one-time';
$firstName = htmlspecialchars(trim($body['firstName'] ?? ''), ENT_QUOTES);
$lastName  = htmlspecialchars(trim($body['lastName'] ?? ''), ENT_QUOTES);
$email     = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$amount || $amount < 1) {
    jsonError('Invalid donation amount');
}
if (!$email) {
    jsonError('Invalid email address');
}
if (!$firstName) {
    jsonError('First name is required');
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO donations (first_name, last_name, email, amount, frequency, created_at)
         VALUES (:first_name, :last_name, :email, :amount, :frequency, NOW())'
    );
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':email'      => $email,
        ':amount'     => $amount,
        ':frequency'  => $frequency,
    ]);

    $donationId = $pdo->lastInsertId();

    jsonResponse([
        'success'     => true,
        'message'     => "Thank you, {$firstName}! Your donation of \${$amount} has been received.",
        'donationId'  => $donationId,
        'amount'      => $amount,
        'frequency'   => $frequency,
    ]);
} catch (PDOException $e) {
    error_log("Database error in donate.php: " . $e->getMessage());
    jsonError('A database error occurred while processing your donation. Please try again later.', 500);
}
