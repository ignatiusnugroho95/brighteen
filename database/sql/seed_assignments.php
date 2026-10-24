<?php

require_once __DIR__ . '/../../../../htdocs/brighteen/bootstrap/app.php';

$pdo = db();

// Seed initial assignments
$pdo->exec("
INSERT INTO `assignments` (`id`, `assigned_by_user_id`, `assigned_to_user_id`, `category_id`, `topic`, `description`, `submission_deadline`, `publishing_schedule`, `status`, `article_id`, `turned_in_at`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 1, 'Generative AI in High School Classrooms: Cheating Tool or Creative Superpower?', 'Investigate how students and teachers are collaborating with generative tools for coding, creative writing, and science lab simulations. Include ethical ground rules and interview quotes.', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), 'pending', NULL, NULL, NOW(), NOW()),
(2, 2, 3, 3, 'Inside the High School Esports Championship: Meta Shifts & Student MVPs', 'Break down the finals of the regional inter-school Valorant tournament. Highlight top clutches, team communication, and the rising culture of campus gaming clubs.', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY), 'pending', NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(3, 1, 7, 2, 'Nostalgia Wave: Why 2000s Digicams Are Dominating School Yearbooks', 'Explore the massive resurgence of vintage CCD cameras, low-res flash aesthetics, and why teen creators are leaving smartphones behind for authentic campus memories.', DATE_ADD(NOW(), INTERVAL 6 HOUR), DATE_ADD(NOW(), INTERVAL 3 DAY), 'pending', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(4, 2, 3, 4, 'How Teenage VTubers Are Building Global Micro-Communities', 'Case study on student 2D/3D avatar creators streaming digital art, lo-fi beats, and coding tutorials from their bedrooms.', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'turned_in', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NOW())
ON DUPLICATE KEY UPDATE `topic` = VALUES(`topic`);
");

echo "Seeded 4 assignments successfully!\n";
