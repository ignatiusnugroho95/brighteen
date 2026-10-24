<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/SystemSetting.php';

class ContributorApplication extends Model
{
    protected static $table = 'contributor_applications';

    public static function allWithUsers()
    {
        $stmt = db()->query("
            SELECT ca.*, 
                   u.name as current_user_name, u.avatar as user_avatar, u.role_id,
                   r.name as role_name, r.display_name as role_display_name,
                   rev.name as reviewer_name
            FROM contributor_applications ca
            JOIN users u ON ca.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN users rev ON ca.reviewed_by = rev.id
            ORDER BY 
                CASE WHEN ca.status = 'pending' THEN 1 ELSE 2 END,
                ca.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function pendingCount()
    {
        $stmt = db()->query("SELECT COUNT(*) as cnt FROM contributor_applications WHERE status = 'pending'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    public static function findWithUser($id)
    {
        $stmt = db()->prepare("
            SELECT ca.*, 
                   u.name as current_user_name, u.avatar as user_avatar, u.role_id,
                   r.name as role_name, r.display_name as role_display_name,
                   rev.name as reviewer_name
            FROM contributor_applications ca
            JOIN users u ON ca.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN users rev ON ca.reviewed_by = rev.id
            WHERE ca.id = ?
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findLatestByUser($userId)
    {
        $stmt = db()->prepare("
            SELECT ca.*, rev.name as reviewer_name
            FROM contributor_applications ca
            LEFT JOIN users rev ON ca.reviewed_by = rev.id
            WHERE ca.user_id = ?
            ORDER BY ca.id DESC
            LIMIT 1
        ");
        $stmt->execute([(int)$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function approve($id, $reviewerId)
    {
        $app = self::findWithUser($id);
        if (!$app) {
            return false;
        }

        // 1. Update application status
        $stmt = db()->prepare("
            UPDATE contributor_applications 
            SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL
            WHERE id = ?
        ");
        $stmt->execute([(int)$reviewerId, (int)$id]);

        // 2. Promote user to Contributor (role_id = 4)
        // Also update name to author_name if specified
        $authorName = !empty($app['author_name']) ? trim($app['author_name']) : $app['full_name'];
        $userUpdate = [
            'role_id' => 4, // community contributor role
        ];
        if (!empty($authorName)) {
            $userUpdate['name'] = $authorName;
        }

        $fields = [];
        $params = [];
        foreach ($userUpdate as $k => $v) {
            $fields[] = "`$k` = ?";
            $params[] = $v;
        }
        $params[] = (int)$app['user_id'];
        $updateSql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        db()->prepare($updateSql)->execute($params);

        // 3. Send Notification to user
        Notification::send(
            $app['user_id'],
            'assignment_promoted',
            '🎉 You are now a Contributor!',
            'Congratulations! Management has approved your contributor application. You can now access the Writer Workbench and publish story drafts.',
            'articles/create'
        );

        // 4. Automatically announce promotion on the ticker!
        $institutionStr = !empty($app['institution']) ? " ({$app['institution']})" : "";
        $tickerText = "🎉 Congratulations to {$authorName}{$institutionStr} on being officially promoted to Brighteen Community Contributor! Welcome to the creator team!";
        SystemSetting::set('announcement_text', $tickerText, 'Active platform-wide announcement on homepage ticker');

        return true;
    }

    public static function reject($id, $reviewerId, $reason = null)
    {
        $app = self::findWithUser($id);
        if (!$app) {
            return false;
        }

        $stmt = db()->prepare("
            UPDATE contributor_applications 
            SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([(int)$reviewerId, $reason, (int)$id]);

        // Send Notification to user
        $feedbackMsg = $reason ? " Editorial Feedback: \"$reason\"" : "";
        Notification::send(
            $app['user_id'],
            'submission_rejected',
            'Contributor Application Update',
            'Your application to become a Contributor was reviewed by Management.' . $feedbackMsg . ' You may revise and submit again.',
            'about#get-involved'
        );

        return true;
    }
}
