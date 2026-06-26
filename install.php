<?php
session_start();

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    die("System is already installed. If you need to reinstall, please delete the .env file.");
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '5432';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';
    
    $adminUser = $_POST['admin_user'] ?? '';
    $adminPass = $_POST['admin_pass'] ?? '';

    if (!$dbName || !$dbUser || !$adminUser || !$adminPass) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // 1. Test Database Connection (PostgreSQL / Supabase)
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 2. Read and run PostgreSQL schema
            $schemaFile = __DIR__ . '/api/schema_pg.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("api/schema.sql not found. Make sure all files were extracted correctly.");
            }
            
            $sql = file_get_contents($schemaFile);
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($queries as $query) {
                if ($query) {
                    $pdo->exec($query);
                }
            }

            // 3. Create Admin User (Table should exist now)
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )");

            // Clear any existing users to be safe? No, schema.sql creates the table.
            
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON CONFLICT (username) DO UPDATE SET password_hash = EXCLUDED.password_hash");
            $stmt->execute([$adminUser, $hash]);

            // 4. Create .env file
            $envContent = implode("\n", [
                "DB_HOST=$dbHost",
                "DB_PORT=$dbPort",
                "DB_NAME=$dbName",
                "DB_USER=$dbUser",
                "DB_PASS=$dbPass",
            ]);
            
            if (file_put_contents($envPath, $envContent) === false) {
                throw new Exception("Failed to write .env file. Please check folder permissions.");
            }

            $success = true;
            
        } catch (Exception $e) {
            $error = "Installation failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HSI Setup Wizard</title>
<style>
  * { box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
  body { background: #F8F8F6; color: #1a1a1a; display: flex; justify-content: center; padding: 40px 20px; margin: 0; }
  .card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 500px; width: 100%; }
  h1 { margin-top: 0; color: #1A2B4A; font-size: 24px; margin-bottom: 24px; text-align: center; }
  .form-group { margin-bottom: 16px; }
  label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
  input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
  input:focus { border-color: #0D7B6E; outline: none; }
  .btn { display: block; width: 100%; background: #C8102E; color: white; border: none; padding: 14px; font-size: 16px; font-weight: 600; border-radius: 4px; cursor: pointer; text-align: center; text-decoration: none; margin-top: 24px; }
  .btn:hover { background: #a00d24; }
  .btn-secondary { background: #0D7B6E; margin-top: 12px; }
  .btn-secondary:hover { background: #0b685c; }
  .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
  .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: center; line-height: 1.5; }
  hr { border: none; border-top: 1px solid #eee; margin: 32px 0; }
</style>
</head>
<body>

<div class="card">
    <?php if ($success): ?>
        <h1>Installation Complete! 🎉</h1>
        <div class="success">The database has been provisioned and the admin user has been created successfully.</div>
        <p style="text-align:center;font-size:14px;color:#666">For security reasons, this installer file will delete itself now.</p>
        
        <a href="admin/" class="btn">Go to Admin Panel</a>
        <a href="index.php" class="btn btn-secondary">View Website</a>
        
        <?php 
        // Self-destruct logic using a shutdown function to allow HTML to render first
        register_shutdown_function(function() {
            @unlink(__FILE__);
        });
        ?>
    <?php else: ?>
        <h1>System Setup</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 24px; padding: 12px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-size: 13px; line-height: 1.6;">
                Welcome! Please provide your MySQL database credentials below. If the database does not exist, we will try to create it for you.
            </div>

            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div style="display: flex; gap: 16px;">
                <div class="form-group" style="flex: 2;">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="hsi_cms" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Port</label>
                    <input type="text" name="db_port" value="5432" required>
                </div>
            </div>
            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" placeholder="root" required>
            </div>
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass" placeholder="Leave blank if none">
            </div>

            <hr>
            
            <h3 style="margin-top:0;font-size:18px;color:#1A2B4A">Admin Account Setup</h3>
            <div class="form-group">
                <label>Admin Username (or Email)</label>
                <input type="text" name="admin_user" placeholder="admin@example.com" required>
            </div>
            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="admin_pass" required minlength="6">
            </div>

            <button type="submit" class="btn">Install System</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
