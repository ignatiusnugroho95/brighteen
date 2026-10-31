<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/SystemSetting.php';

class Announcement extends Model
{
    protected static $table = 'announcements';

    public const MAX_ACTIVE = 5;
    public const MAX_TEXT_LENGTH = 160;
    public const MAX_LINK_URL_LENGTH = 200;
    public const MAX_LINK_LABEL_LENGTH = 30;

    private static $tableEnsured = false;

    /**
     * Ensure the announcements table exists and migrate legacy announcement_text if needed.
     */
    public static function ensureTable()
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        try {
            db()->exec("
                CREATE TABLE IF NOT EXISTS `announcements` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `emoji` VARCHAR(16) NOT NULL DEFAULT '📢',
                    `text` VARCHAR(200) NOT NULL,
                    `link_url` VARCHAR(255) DEFAULT NULL,
                    `link_label` VARCHAR(50) DEFAULT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `sort_order` INT NOT NULL DEFAULT 0,
                    `created_by` INT UNSIGNED DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Check if we should migrate legacy announcement_text on initial setup
            $migrated = SystemSetting::get('announcements_migrated', '0');
            if ($migrated !== '1') {
                $count = (int) db()->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
                if ($count === 0) {
                    $legacyText = trim((string) SystemSetting::get('announcement_text', ''));
                    if ($legacyText !== '') {
                        $cleanText = mb_substr($legacyText, 0, self::MAX_TEXT_LENGTH);
                        $stmt = db()->prepare("
                            INSERT INTO announcements (emoji, text, link_url, link_label, is_active, sort_order)
                            VALUES (?, ?, NULL, NULL, 1, 1)
                        ");
                        $stmt->execute(['📢', $cleanText]);
                    }
                }
                SystemSetting::set('announcements_migrated', '1', 'Whether legacy announcement_text was migrated to announcements table');
            }
        } catch (\Throwable $e) {
            // Silently ignore if DB error
        }
    }

    /**
     * Get all announcements with creator details, ordered by active status and newest first.
     */
    public static function allWithCreator()
    {
        self::ensureTable();
        $stmt = db()->query("
            SELECT a.*, u.name as creator_name
            FROM announcements a
            LEFT JOIN users u ON a.created_by = u.id
            ORDER BY a.is_active DESC, a.sort_order ASC, a.updated_at DESC, a.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get up to 5 active announcements for the homepage ticker.
     */
    public static function getActive($limit = self::MAX_ACTIVE)
    {
        self::ensureTable();
        $limit = max(1, min((int) $limit, self::MAX_ACTIVE));
        $stmt = db()->query("
            SELECT * FROM announcements
            WHERE is_active = 1
            ORDER BY sort_order ASC, updated_at DESC, id DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count currently active announcements.
     */
    public static function activeCount($excludeId = null)
    {
        self::ensureTable();
        if ($excludeId !== null) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM announcements WHERE is_active = 1 AND id != ?");
            $stmt->execute([(int) $excludeId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) db()->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1")->fetchColumn();
    }

    /**
     * Normalize a link URL so internal paths or full URLs work cleanly.
     */
    public static function formatHref($linkUrl)
    {
        $linkUrl = trim((string) $linkUrl);
        if ($linkUrl === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $linkUrl)) {
            return $linkUrl;
        }
        return url(ltrim($linkUrl, '/'));
    }
}
