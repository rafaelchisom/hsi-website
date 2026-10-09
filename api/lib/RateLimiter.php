<?php

/**
 * Generic per-IP rate limiting for public (unauthenticated) endpoints, using
 * the same "count recent rows" approach AuthController already uses for
 * login lockout, generalised to a shared table so any endpoint can opt in
 * with one call. Fails open (never blocks the request) if the DB check
 * itself errors — a broken rate limiter must never take down a public form.
 */
class RateLimiter
{
    public static function enforce(string $bucket, int $maxAttempts, int $windowMinutes): void
    {
        $pdo = Database::get();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits
                WHERE bucket = ? AND ip_address = ? AND created_at > NOW() - CAST(? AS INTEGER) * INTERVAL '1 minute'");
            $stmt->execute([$bucket, $ip, $windowMinutes]);
            if ((int) $stmt->fetchColumn() >= $maxAttempts) {
                Response::error('Too many requests. Please try again later.', 429);
            }

            $pdo->prepare('INSERT INTO rate_limits (bucket, ip_address) VALUES (?, ?)')->execute([$bucket, $ip]);

            // Opportunistic cleanup so the table doesn't grow unbounded — no
            // cron needed, cheap enough to run on a small fraction of requests.
            if (random_int(1, 100) === 1) {
                $pdo->exec("DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '1 day'");
            }
        } catch (Throwable $e) {
            error_log('[RateLimiter] ' . $e->getMessage());
        }
    }
}
