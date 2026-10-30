<?php
require_once __DIR__ . '/../../bootstrap/app.php';

$db = db();

echo "Running migrations for Video, Carousel, and Polls...\n";

// 1. Add columns to articles if not exists
$columns = [
    'post_format' => "VARCHAR(30) NOT NULL DEFAULT 'article' AFTER `featured_image`",
    'video_url' => "VARCHAR(500) NULL AFTER `post_format`",
    'video_type' => "VARCHAR(30) NULL AFTER `video_url`",
    'carousel_images' => "LONGTEXT NULL AFTER `video_type`"
];

foreach ($columns as $col => $definition) {
    try {
        $check = $db->query("SHOW COLUMNS FROM `articles` LIKE '{$col}'")->fetch();
        if (!$check) {
            $db->exec("ALTER TABLE `articles` ADD `{$col}` {$definition}");
            echo "Added column `{$col}` to articles.\n";
        } else {
            echo "Column `{$col}` already exists.\n";
        }
    } catch (\Throwable $e) {
        echo "Error checking/adding column {$col}: " . $e->getMessage() . "\n";
    }
}

// 2. Create polls table
$db->exec("
CREATE TABLE IF NOT EXISTS `polls` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_id` BIGINT UNSIGNED NOT NULL,
    `question` VARCHAR(255) NOT NULL,
    `poll_type` ENUM('poll', 'quiz') NOT NULL DEFAULT 'poll',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_polls_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `polls` checked/created.\n";

// 3. Create poll_options table
$db->exec("
CREATE TABLE IF NOT EXISTS `poll_options` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `poll_id` BIGINT UNSIGNED NOT NULL,
    `option_text` VARCHAR(255) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `order_num` INT NOT NULL DEFAULT 0,
    `votes_count` INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `poll_options` checked/created.\n";

// 4. Create poll_votes table
$db->exec("
CREATE TABLE IF NOT EXISTS `poll_votes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `poll_id` BIGINT UNSIGNED NOT NULL,
    `option_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `session_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_poll_vote` (`poll_id`, `ip_address`, `session_id`),
    CONSTRAINT `fk_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_votes_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `poll_votes` checked/created.\n";

echo "Migration finished successfully!\n";
