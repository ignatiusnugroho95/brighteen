<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class Comment extends Model
{
    protected static $table = 'comments';

    public static function forArticle($articleId)
    {
        $sql = "
            SELECT c.*, u.name as user_name, u.avatar as user_avatar, r.name as role_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE c.article_id = ?
            ORDER BY c.created_at DESC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
