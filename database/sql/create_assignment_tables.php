<?php

require_once __DIR__ . '/../../../../htdocs/brighteen/bootstrap/app.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS `assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assigned_by_user_id` BIGINT UNSIGNED NOT NULL,
    `assigned_to_user_id` BIGINT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `topic` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `submission_deadline` DATETIME NOT NULL,
    `publishing_schedule` DATETIME NOT NULL,
    `status` ENUM('pending', 'turned_in', 'approved', 'expired') NOT NULL DEFAULT 'pending',
    `article_id` BIGINT UNSIGNED NULL,
    `turned_in_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assignee_status` (`assigned_to_user_id`, `status`),
    INDEX `idx_deadlines` (`submission_deadline`, `publishing_schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Tables assignments & notifications created successfully!\n";
