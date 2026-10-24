<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class Category extends Model
{
    protected static $table = 'categories';

    public static function allWithArticleCount()
    {
        $stmt = db()->query("
            SELECT c.*, 
                   COUNT(CASE WHEN a.status = 'published' THEN a.id END) as published_articles_count,
                   COUNT(a.id) as total_articles_count
            FROM categories c
            LEFT JOIN articles a ON c.id = a.category_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
