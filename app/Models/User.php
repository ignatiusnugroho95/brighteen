<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected static $table = 'users';

    public static function allWithRoles()
    {
        $stmt = db()->query("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function staffWithRoles()
    {
        $stmt = db()->query("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE r.name IN ('management', 'editor', 'journalist', 'contributor')
            ORDER BY r.id ASC, u.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function readersWithRoles()
    {
        $stmt = db()->query("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name,
                   (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.id) as comments_count,
                   (SELECT COUNT(*) FROM saved_posts sp WHERE sp.user_id = u.id) as bookmarks_count,
                   ca.id as application_id, ca.status as application_status, ca.institution, ca.grade_or_position, ca.whatsapp, ca.birth_date, ca.author_name as app_author_name, ca.created_at as applied_at
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN (
                SELECT ca1.* FROM contributor_applications ca1
                INNER JOIN (SELECT user_id, MAX(id) as max_id FROM contributor_applications GROUP BY user_id) ca2 
                ON ca1.id = ca2.max_id
            ) ca ON ca.user_id = u.id
            WHERE r.name IN ('reader_registered', 'reader_guest')
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findWithRole($id)
    {
        $stmt = db()->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
