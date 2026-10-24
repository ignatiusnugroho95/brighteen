<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class Article extends Model
{
    protected static $table = 'articles';

    public static function getPublished($limit = 12, $categoryId = null, $search = null)
    {
        $currentUserId = auth()->id() ?: 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $where = "a.status = 'published'";
        $params = [$currentUserId, $currentUserId, $ip];

        if ($categoryId) {
            $where .= " AND a.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search) {
            $where .= " AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $limitSql = $limit ? "LIMIT " . (int)$limit : "";

        $sql = "
            SELECT a.*, 
                   COALESCE(NULLIF(a.featured_image, ''), 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80') as featured_image,
                   u.name as author_name, u.avatar as author_avatar, u.bio as author_bio,
                   c.name as category_name, c.slug as category_slug, c.color_code as category_color, c.icon as category_icon,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count,
                   (SELECT COUNT(*) FROM likes lk WHERE lk.article_id = a.id) as likes_count,
                   (SELECT COUNT(*) FROM saved_posts spc WHERE spc.article_id = a.id) as saves_count,
                   (SELECT COUNT(*) FROM saved_posts sp WHERE sp.article_id = a.id AND sp.user_id = ?) as is_saved,
                   (SELECT COUNT(*) FROM likes lk2 WHERE lk2.article_id = a.id AND (lk2.user_id = ? OR lk2.ip_address = ?)) as is_liked
            FROM articles a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE {$where}
            ORDER BY a.published_at DESC, a.id DESC
            {$limitSql}
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getLatestUpdated($limit = 5)
    {
        $sql = "
            SELECT a.id, a.title, a.slug, a.updated_at, a.published_at, a.content,
                   c.name as category_name, c.slug as category_slug, c.color_code as category_color, c.icon as category_icon
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.updated_at DESC, a.published_at DESC
            LIMIT " . (int)$limit;

        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTrending($limit = 6)
    {
        $currentUserId = auth()->id() ?: 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $sql = "
            SELECT a.*, 
                   COALESCE(NULLIF(a.featured_image, ''), 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80') as featured_image,
                   u.name as author_name, u.avatar as author_avatar,
                   c.name as category_name, c.slug as category_slug, c.color_code as category_color, c.icon as category_icon,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count,
                   (SELECT COUNT(*) FROM likes lk WHERE lk.article_id = a.id) as likes_count,
                   (SELECT COUNT(*) FROM saved_posts spc WHERE spc.article_id = a.id) as saves_count,
                   (SELECT COUNT(*) FROM saved_posts sp WHERE sp.article_id = a.id AND sp.user_id = ?) as is_saved,
                   (SELECT COUNT(*) FROM likes lk2 WHERE lk2.article_id = a.id AND (lk2.user_id = ? OR lk2.ip_address = ?)) as is_liked
            FROM articles a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY (a.views_count * 1.5 + (SELECT COUNT(*) FROM likes WHERE article_id = a.id) * 3) DESC
            LIMIT " . (int)$limit;

        $stmt = db()->prepare($sql);
        $stmt->execute([$currentUserId, $currentUserId, $ip]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findWithDetails($slugOrId)
    {
        $currentUserId = auth()->id() ?: 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $field = is_numeric($slugOrId) ? 'a.id' : 'a.slug';

        $sql = "
            SELECT a.*, 
                   COALESCE(NULLIF(a.featured_image, ''), 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80') as featured_image,
                   u.name as author_name, u.avatar as author_avatar, u.bio as author_bio,
                   c.name as category_name, c.slug as category_slug, c.color_code as category_color, c.icon as category_icon,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count,
                   (SELECT COUNT(*) FROM likes lk WHERE lk.article_id = a.id) as likes_count,
                   (SELECT COUNT(*) FROM saved_posts spc WHERE spc.article_id = a.id) as saves_count,
                   (SELECT COUNT(*) FROM saved_posts sp WHERE sp.article_id = a.id AND sp.user_id = ?) as is_saved,
                   (SELECT COUNT(*) FROM likes lk2 WHERE lk2.article_id = a.id AND (lk2.user_id = ? OR lk2.ip_address = ?)) as is_liked
            FROM articles a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE {$field} = ?
            LIMIT 1
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute([$currentUserId, $currentUserId, $ip, $slugOrId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getPendingReview()
    {
        $sql = "
            SELECT a.*, 
                   u.name as author_name, u.email as author_email, u.avatar as author_avatar,
                   c.name as category_name, c.color_code as category_color
            FROM articles a
            JOIN users u ON a.user_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'pending_review'
            ORDER BY a.updated_at ASC
        ";
        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByUser($userId)
    {
        $sql = "
            SELECT a.*, 
                   c.name as category_name, c.color_code as category_color,
                   (SELECT COUNT(*) FROM likes lk WHERE lk.article_id = a.id) as likes_count,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            WHERE a.user_id = ?
            ORDER BY a.updated_at DESC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function incrementViews($id)
    {
        $stmt = db()->prepare("UPDATE articles SET views_count = views_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get all unpublished drafts (draft, pending_review, rejected) for a specific author
     */
    public static function getUnpublishedByUser($userId)
    {
        $sql = "
            SELECT a.*, 
                   COALESCE(NULLIF(a.featured_image, ''), 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80') as featured_image,
                   c.name as category_name, c.slug as category_slug, c.color_code as category_color, c.icon as category_icon,
                   asg.id as linked_assignment_id, asg.topic as linked_assignment_topic, asg.submission_deadline
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN assignments asg ON asg.article_id = a.id
            WHERE a.user_id = ? AND a.status IN ('draft', 'pending_review', 'rejected')
            ORDER BY a.updated_at DESC, a.id DESC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete an unpublished draft belonging to the authenticated author
     */
    public static function deleteForAuthor($id, $userId)
    {
        $stmt = db()->prepare("DELETE FROM articles WHERE id = ? AND user_id = ? AND status IN ('draft', 'pending_review', 'rejected')");
        return $stmt->execute([(int)$id, (int)$userId]);
    }
}

