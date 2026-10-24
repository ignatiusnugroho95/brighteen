<?php

namespace App\Models;

require_once __DIR__ . '/Model.php';

class Like extends Model
{
    protected static $table = 'likes';

    public static function toggle($articleId, $userId, $ipAddress)
    {
        $existing = null;
        if ($userId) {
            $stmt = db()->prepare("SELECT * FROM likes WHERE article_id = ? AND user_id = ?");
            $stmt->execute([$articleId, $userId]);
            $existing = $stmt->fetch();
        } else {
            $stmt = db()->prepare("SELECT * FROM likes WHERE article_id = ? AND ip_address = ? AND user_id IS NULL");
            $stmt->execute([$articleId, $ipAddress]);
            $existing = $stmt->fetch();
        }

        if ($existing) {
            db()->prepare("DELETE FROM likes WHERE id = ?")->execute([$existing['id']]);
            $liked = false;
        } else {
            $stmt = db()->prepare("INSERT INTO likes (article_id, user_id, ip_address, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$articleId, $userId ?: null, $ipAddress]);
            $liked = true;
        }

        // Return updated count
        $cntStmt = db()->prepare("SELECT COUNT(*) as count FROM likes WHERE article_id = ?");
        $cntStmt->execute([$articleId]);
        $res = $cntStmt->fetch();

        return [
            'liked' => $liked,
            'count' => (int)($res['count'] ?? 0)
        ];
    }
}
