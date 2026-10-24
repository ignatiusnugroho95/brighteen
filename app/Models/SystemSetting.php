<?php

namespace App\Models;

require_once __DIR__ . '/Model.php';

class SystemSetting extends Model
{
    protected static $table = 'system_settings';

    public static function get($key, $default = null)
    {
        $stmt = db()->prepare("SELECT value FROM system_settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }

    public static function set($key, $value, $description = null)
    {
        $stmt = db()->prepare("
            INSERT INTO system_settings (`key`, `value`, `description`) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = COALESCE(VALUES(`description`), `description`)
        ");
        return $stmt->execute([$key, $value, $description]);
    }
}
