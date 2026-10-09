<?php
/**
 * Create an admin user, or reset an existing user's password and make them admin.
 * Usage (e.g. from the Render shell): php db/create_admin.php "Full Name" "email@example.com" "password"
 */

require __DIR__ . '/../api/config.php';
require __DIR__ . '/../api/lib/Database.php';

[, $name, $email, $password] = $argv + [null, null, null, null];
if (!$name || !$email || !$password) {
    fwrite(STDERR, "Usage: php db/create_admin.php \"Full Name\" \"email@example.com\" \"password\"\n");
    exit(1);
}

$stmt = Database::get()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)
    ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name, password_hash = EXCLUDED.password_hash, role = EXCLUDED.role');
$stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 'admin']);

echo "Admin user ready: $email\n";
