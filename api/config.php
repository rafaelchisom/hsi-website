<?php

// PHP 7.4 compatibility: these are native from PHP 8.0. Shared hosts (and MAMP
// after a restart) can still be on 7.4, so define them before anything else runs.
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

/**
 * Loads KEY=VALUE pairs from a .env file (if present) into getenv(), without
 * pulling in a Composer dependency. Lines starting with # and blank lines
 * are skipped. Existing environment variables are never overwritten.
 */
function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

/** Fallback when SITE_URL isn't configured: scheme + host + the folder above /api. */
function guessSiteUrl(): string
{
    if (!isset($_SERVER['HTTP_HOST'])) {
        return 'http://localhost';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'))), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
// Supabase requires SSL; set DB_SSL=disable only for a local Postgres.
define('DB_SSL', getenv('DB_SSL') ?: 'require');

// Detailed error messages in API responses — set APP_DEBUG=true only for local
// dev, so exceptions aren't exposed to clients (they're always still logged via
// error_log()).
define('APP_DEBUG', (getenv('APP_DEBUG') ?: 'false') === 'true');

// Uploaded images go to Supabase Storage when these are set (needed on Render,
// whose disk is wiped on every deploy/restart), otherwise to api/uploads/.
define('SUPABASE_URL', rtrim(getenv('SUPABASE_URL') ?: '', '/'));
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: '');
define('SUPABASE_BUCKET', getenv('SUPABASE_BUCKET') ?: 'media');

// Behind Render's proxy REMOTE_ADDR is the proxy, not the visitor — use the
// client address it forwards so per-IP rate limits and login lockouts apply
// to each visitor rather than to everyone at once.
if ((getenv('TRUST_PROXY') ?: 'false') === 'true') {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $_SERVER['REMOTE_ADDR'] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
}

// Canonical public site URL, used e.g. by the sitemap. Override in .env.
define('SITE_URL', rtrim(getenv('SITE_URL') ?: guessSiteUrl(), '/'));

// Path portion of SITE_URL ('' when the site lives at the domain root) — used to
// build root-relative URLs such as uploaded-image links.
define('SITE_PATH', rtrim((string) parse_url(SITE_URL, PHP_URL_PATH), '/'));
