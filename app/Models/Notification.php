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
}
