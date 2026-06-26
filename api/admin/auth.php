<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_SESSION['admin_user'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            jsonResponse(['success' => false, 'error' => 'Session expired']);
        }
        $_SESSION['last_activity'] = time();
        jsonResponse(['success' => true, 'username' => $_SESSION['admin_user']]);
    }
    jsonResponse(['success' => false]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = getJsonBody();
    $user    = trim($body['username'] ?? '');
    $pass    = trim($body['password'] ?? '');
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $website = trim($body['website'] ?? '');

    if (!empty($website)) {
        sleep(5);
        jsonError('Invalid credentials', 401);
    }

    try {
        $db = getDB();

        // Cleanup attempts older than 15 minutes
        $db->exec("DELETE FROM login_attempts WHERE last_attempt < NOW() - INTERVAL '15 minutes'");

        // Check if IP is blocked
        $stmt = $db->prepare("SELECT attempts FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $attempts = $stmt->fetchColumn();

        if ($attempts && $attempts >= 5) {
            sleep(2);
            jsonResponse(['success' => false, 'error' => 'Too many login attempts. Please try again in 15 minutes.'], 429);
        }

        $stmt = $db->prepare('SELECT password_hash FROM admin_users WHERE username = ?');
        $stmt->execute([$user]);
        $row  = $stmt->fetch();

        if ($row && password_verify($pass, $row['password_hash'])) {
            $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            session_regenerate_id(true);
            $token = bin2hex(random_bytes(32));
            $_SESSION['admin_token']   = $token;
            $_SESSION['admin_user']    = $user;
            $_SESSION['last_activity'] = time();
            jsonResponse(['success' => true, 'token' => $token, 'username' => $user]);
        } else {
            // PostgreSQL upsert for login_attempts
            $db->prepare(
                "INSERT INTO login_attempts (ip_address, attempts, last_attempt)
                 VALUES (?, 1, NOW())
                 ON CONFLICT (ip_address)
                 DO UPDATE SET attempts = login_attempts.attempts + 1, last_attempt = NOW()"
            )->execute([$ip]);
            sleep(2);
            jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Database unavailable'], 503);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    session_destroy();
    jsonResponse(['success' => true]);
}

jsonError('Method not allowed', 405);
