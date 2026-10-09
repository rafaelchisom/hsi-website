<?php

class DashboardController
{
    public static function routes(Router $r): void
    {
        $r->get('/dashboard/stats', [self::class, 'stats']);
    }

    public static function stats(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();

        $unreadContactSubmissions = (int) $pdo->query(
            'SELECT COUNT(*) FROM contact_submissions WHERE is_read = 0'
        )->fetchColumn();

        $draftNewsCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM news_articles WHERE status = 'draft'"
        )->fetchColumn();

        $scheduledNewsCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM news_articles WHERE status = 'scheduled'"
        )->fetchColumn();

        $teamMemberCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM team_members'
        )->fetchColumn();

        $mediaCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM media'
        )->fetchColumn();

        $stmt = $pdo->query(
            'SELECT al.id, al.action, al.resource_type, al.resource_id, al.description, al.created_at, u.name AS user_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.id DESC
             LIMIT 10'
        );
        $recentActivity = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'user_name' => $row['user_name'],
                'action' => $row['action'],
                'resource_type' => $row['resource_type'],
                'resource_id' => $row['resource_id'] !== null ? (int) $row['resource_id'] : null,
                'description' => $row['description'],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll());

        Response::json([
            'unread_contact_submissions' => $unreadContactSubmissions,
            'draft_news_count' => $draftNewsCount,
            'scheduled_news_count' => $scheduledNewsCount,
            'team_member_count' => $teamMemberCount,
            'media_count' => $mediaCount,
            'recent_activity' => $recentActivity,
        ]);
    }
}
