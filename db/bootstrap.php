<?php
/**
 * Prepares the database on every container start (see docker-entrypoint.sh),
 * replacing the cPanel install.php wizard, whose config files and edits would
 * be wiped on Render's next deploy. Safe to run repeatedly:
 *
 *   1. applies any db/migrations/*.sql not yet recorded in schema_migrations
 *   2. on a brand-new database, imports the starting content (db/content.sql)
 *   3. if there are no users yet and ADMIN_EMAIL / ADMIN_PASSWORD are set,
 *      creates the first admin account
 *   4. with Supabase Storage configured, makes sure the image bucket exists
 *      and holds the starting content's images
 *
 * Steps 1-3 run in one transaction under an advisory lock, so a failed first
 * install leaves the database untouched and two instances starting at once
 * (zero-downtime deploys) can't both apply the same migration.
 *
 * Usage: php db/bootstrap.php
 */

require __DIR__ . '/../api/config.php';
require __DIR__ . '/../api/lib/Database.php';
require __DIR__ . '/../api/lib/Uploads.php';

function say(string $msg): void
{
    echo "[bootstrap] $msg\n";
}

$pdo = Database::get();
$pdo->beginTransaction();
try {
    $pdo->query("SELECT pg_advisory_xact_lock(hashtext('dhaf-bootstrap'))");

    $tableExists = $pdo->prepare('SELECT to_regclass(?) IS NOT NULL');
    $tableExists->execute(['schema_migrations']);
    $fresh = !$tableExists->fetchColumn();
    if ($fresh) {
        // Refuse to build on top of another app's tables (e.g. the old HSI
        // site's team_members / news_articles, which have different columns).
        $tableExists->execute(['users']);
        $hasUsers = $tableExists->fetchColumn();
        $tableExists->execute(['news_articles']);
        if ($hasUsers || $tableExists->fetchColumn()) {
            throw new RuntimeException('This database already contains tables from another site. '
                . 'Point DB_* at a new, empty Supabase project (or remove the old tables) and redeploy.');
        }
        $pdo->exec('CREATE TABLE schema_migrations (version VARCHAR(50) PRIMARY KEY, applied_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP)');
    }

    $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob(__DIR__ . '/migrations/*.sql');
    sort($files);
    $ran = 0;
    foreach ($files as $file) {
        $version = basename($file, '.sql');
        if (in_array($version, $applied, true)) {
            continue;
        }
        $pdo->exec(file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
        $ran++;
    }
    say($ran === 0 ? 'Database schema is up to date.' : "Applied $ran migration(s).");

    if ($fresh) {
        $pdo->exec(file_get_contents(__DIR__ . '/content.sql'));
        say('Imported the starting content.');
    }

    $adminEmail = trim((string) getenv('ADMIN_EMAIL'));
    $adminPassword = (string) getenv('ADMIN_PASSWORD');
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        if ($adminEmail === '' || $adminPassword === '') {
            say('No admin account yet — set ADMIN_EMAIL and ADMIN_PASSWORD (and optionally ADMIN_NAME) and redeploy.');
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 10) {
            say('Not creating the admin: ADMIN_EMAIL must be a valid address and ADMIN_PASSWORD at least 10 characters.');
        } else {
            $pdo->prepare("INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, 'admin', 1)")
                ->execute([getenv('ADMIN_NAME') ?: 'Administrator', $adminEmail, password_hash($adminPassword, PASSWORD_BCRYPT)]);
            $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('notify_email', ?)
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value
                WHERE site_settings.setting_value IS NULL OR site_settings.setting_value = ''")
                ->execute([$adminEmail]);
            say("Created the admin account $adminEmail — you can now remove ADMIN_PASSWORD from the environment.");
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, '[bootstrap] FAILED: ' . $e->getMessage() . "\n");
    exit(1);
}

if (Uploads::usesSupabase()) {
    if ($error = Uploads::ensureBucket()) {
        fwrite(STDERR, "[bootstrap] Supabase Storage: $error\n");
        exit(1);
    }
    // The starting content's images ship in api/uploads/; copy them to the bucket.
    $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    foreach (glob(Uploads::LOCAL_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($error = Uploads::publish(basename($file), $mimes[$ext])) {
            fwrite(STDERR, "[bootstrap] $error\n");
        }
    }
    say('Supabase Storage bucket "' . SUPABASE_BUCKET . '" is ready.');
} else {
    say('SUPABASE_URL / SUPABASE_SERVICE_KEY not set — uploads are stored on local disk and will be lost on redeploy.');
}
