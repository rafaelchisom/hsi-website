<?php

/**
 * Lightweight audit trail. Call ::record() from a controller right after a
 * mutation succeeds. Never throws — a logging failure should never break the
 * actual request.
 */
class ActivityLog
{
    public static function record(string $action, string $resourceType, ?int $resourceId, ?string $description = null): void
    {
        try {
            Database::get()
                ->prepare('INSERT INTO activity_log (user_id, action, resource_type, resource_id, description) VALUES (?, ?, ?, ?, ?)')
                ->execute([Auth::currentUserId(), $action, $resourceType, $resourceId, $description]);
        } catch (Throwable $e) {
            error_log('[ActivityLog] failed to record: ' . $e->getMessage());
        }
    }
}
