<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class SavedPost extends Model
{
    protected static $table = 'saved_posts';

    public static function toggle($articleId, $userId)
    {
        $stmt = db()->prepare("SELECT * FROM saved_posts WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            db()->prepare("DELETE FROM saved_posts WHERE id = ?")->execute([$existing['id']]);
            $saved = false;
        } else {
            $stmt = db()->prepare("INSERT INTO saved_posts (article_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$articleId, $userId]);
            $saved = true;
        }

        $cntStmt = db()->prepare("SELECT COUNT(*) as count FROM saved_posts WHERE article_id = ?");
        $cntStmt->execute([$articleId]);
        $res = $cntStmt->fetch();

        return [
            'saved' => $saved,
            'count' => (int)($res['count'] ?? 0)
        ];
    }

    public static function getSavedArticlesForUser($userId)
    {
        $sql = "
            SELECT a.*, 
                   u.name as author_name, u.avatar as author_avatar,
                   c.name as category_name, c.color_code as category_color, c.icon as category_icon,
                   (SELECT COUNT(*) FROM likes lk WHERE lk.article_id = a.id) as likes_count,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count,
                   sp.created_at as saved_at
            FROM saved_posts sp
            JOIN articles a ON sp.article_id = a.id
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE sp.user_id = ?
            ORDER BY sp.created_at DESC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
