<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$body = getJsonBody();

if (!empty($body['_honey'])) {
    jsonResponse(['success' => true]);
}

$name    = trim($body['name'] ?? '');
$email   = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
$org     = trim($body['organisation'] ?? '');
$type    = trim($body['type'] ?? '');
$message = trim($body['message'] ?? '');

if (!$name)    jsonError('Name is required');
if (!$email)   jsonError('Valid email is required');
if (!$message) jsonError('Message is required');

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, org, type, message, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$name, $email, $org, $type, $message]);

    // Send email notification
    require_once __DIR__ . '/lib/smtp.php';
    
    // Fetch settings
    $settingsRaw = $pdo->query("SELECT block_key, value FROM content_blocks WHERE page='settings'")->fetchAll();
    $settings = [];
    foreach ($settingsRaw as $row) {
        $settings[$row['block_key']] = $row['value'];
    }
    
    $recipient = $settings['form_recipient'] ?? '';
    if ($recipient) {
        $subject = "New Contact Form Submission: " . $type;
        $htmlMsg = "
        <div style='font-family:sans-serif;color:#333;line-height:1.6'>
            <h2 style='color:#1A2B4A'>New Submission Received</h2>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Organization:</strong> " . htmlspecialchars($org) . "</p>
            <p><strong>Type:</strong> " . htmlspecialchars($type) . "</p>
            <hr>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        </div>";
        
        send_smtp_email($recipient, $subject, $htmlMsg, $settings);
    }
} catch (PDOException $e) {
    error_log("Database error in contact.php: " . $e->getMessage());
    jsonError('A database error occurred. Please try again later.', 500);
}

jsonResponse(['success' => true]);
