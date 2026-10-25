<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class Notification extends Model
{
    protected static $table = 'notifications';

    public static function getUserNotifications($userId, $limit = 20)
    {
        // Role check: Only management receives contributor application notifications & reminders.
        // Journalists, editors, contributors, and readers have no business receiving them.
        $roleSql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = " . (int)$userId;
        $userRole = db()->query($roleSql)->fetchColumn();

        $excludeTypes = [];
        if ($userRole !== 'management') {
            $excludeTypes = ["'application_received'", "'contributor_application_reminder'"];
        }

        $whereClause = "user_id = ?";
        if (!empty($excludeTypes)) {
            $whereClause .= " AND type NOT IN (" . implode(',', $excludeTypes) . ")";
        }

        $stmt = db()->prepare("
            SELECT * FROM notifications 
            WHERE {$whereClause}
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUnreadCount($userId)
    {
        $roleSql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = " . (int)$userId;
        $userRole = db()->query($roleSql)->fetchColumn();

        $excludeTypes = [];
        if ($userRole !== 'management') {
            $excludeTypes = ["'application_received'", "'contributor_application_reminder'"];
        }

        $whereClause = "user_id = ? AND is_read = 0";
        if (!empty($excludeTypes)) {
            $whereClause .= " AND type NOT IN (" . implode(',', $excludeTypes) . ")";
        }

        $stmt = db()->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE {$whereClause}
        ");
        $stmt->execute([(int)$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['unread_count'] ?? 0);
    }

    public static function markAsRead($id, $userId)
    {
        $stmt = db()->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([(int)$id, (int)$userId]);
    }

    public static function markAllAsRead($userId)
    {
        $stmt = db()->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ?
        ");
        return $stmt->execute([(int)$userId]);
    }

    public static function send($userId, $type, $title, $message, $link = null)
    {
        return self::create([
            'user_id' => (int)$userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function existsToday($userId, $type, $link)
    {
        $stmt = db()->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND type = ? AND link = ? AND DATE(created_at) = CURDATE() 
            LIMIT 1
        ");
        $stmt->execute([(int)$userId, $type, $link]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Notify editors and management when a story is submitted for editorial review and publishing
     */
    public static function notifyEditorsForReview($articleId, $articleTitle, $authorName, $assignmentId = 0)
    {
        $currentUserId = auth()->id();
        
        // Find assigned editor if assignmentId > 0
        $assignedEditorId = 0;
        if ($assignmentId > 0) {
            $stmt = db()->prepare("SELECT assigned_by_user_id FROM assignments WHERE id = ?");
            $stmt->execute([(int)$assignmentId]);
            $assignedEditorId = (int)$stmt->fetchColumn();
        }

        // Query all users with role 'editor' and 'management'
        $stmt = db()->query("
            SELECT u.id 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name IN ('editor', 'management')
        ");
        $recipientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($assignedEditorId > 0 && !in_array($assignedEditorId, $recipientIds)) {
            $recipientIds[] = $assignedEditorId;
        }

        $cleanTitle = trim($articleTitle) ?: 'Story Submission';
        $shortTitle = mb_substr($cleanTitle, 0, 45);
        if (mb_strlen($cleanTitle) > 45) {
            $shortTitle .= '...';
        }
        $title = "Story Ready to Review & Publish: {$shortTitle}";
        $message = "{$authorName} has submitted '{$cleanTitle}' for review and publishing. Waiting in your Editorial Review Desk!";
        $link = "dashboard/editorial#review";

        foreach ($recipientIds as $recipientId) {
            $recipientId = (int)$recipientId;
            // Never notify author themselves if they are editor/management
            if ($recipientId === (int)$currentUserId) {
                continue;
            }

            // If assignmentId > 0 and this is the assigned editor, Assignment::markTurnedIn already notified them of turned in
            if ($assignmentId > 0 && $recipientId === $assignedEditorId) {
                continue;
            }

            self::send(
                $recipientId,
                'article_submitted_for_review',
                $title,
                $message,
                $link
            );
        }
    }
}

