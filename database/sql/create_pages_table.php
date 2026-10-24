<?php

require_once __DIR__ . '/../../bootstrap/app.php';

try {
    $pdo = db();
    
    // Create pages table
    $sql = "
    CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `title` VARCHAR(255) NOT NULL,
        `subtitle` TEXT NULL,
        `hero_image` VARCHAR(500) NULL,
        `content` LONGTEXT NULL,
        `mission` TEXT NULL,
        `manifesto` TEXT NULL,
        `stats_json` JSON NULL,
        `updated_by_user_id` BIGINT UNSIGNED NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pages_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Table `pages` checked/created successfully.\n";

    // Seed default About Us page if not exists
    $stmt = $pdo->prepare("SELECT id FROM `pages` WHERE `slug` = 'about' LIMIT 1");
    $stmt->execute();
    $existing = $stmt->fetch();

    $defaultStats = json_encode([
        ["value" => "150K+", "label" => "Monthly Youth Readers", "icon" => "trending_up"],
        ["value" => "2,400+", "label" => "Campus & Tech Dispatches", "icon" => "auto_stories"],
        ["value" => "85+", "label" => "Student Reporters & Creators", "icon" => "groups"],
        ["value" => "100%", "label" => "Independent & Youth-Led", "icon" => "verified"]
    ]);

    $defaultContent = '<p class="text-lg leading-relaxed mb-6 font-medium text-slate-700 dark:text-slate-300">
    Brighteen was founded with a singular conviction: the stories defining Gen-Z and Gen-Alpha deserve more than 15-second algorithmic dopamine loops and sterile corporate headlines. We are an independent, youth-powered media collective built for and by digital natives who want real substance, tactile curiosity, and authentic voice.
</p>
<p class="mb-6 leading-relaxed text-slate-600 dark:text-slate-300">
    From dissecting artificial intelligence breakthroughs in high school science labs to documenting grassroots gaming tournaments, underground aesthetic revivals, and teenage creator economies—we give youth journalists the platform, editorial mentorship, and creative autonomy they need to break meaningful stories.
</p>
<blockquote class="border-l-4 border-primary pl-6 py-3 my-8 italic text-slate-800 dark:text-slate-200 bg-primary/5 rounded-r-2xl text-base font-medium">
    "Media shouldn\'t treat youth as passive consumers of algorithmic feeds. We are cultural architects, curious coders, and empathetic storytellers shaping tomorrow\'s internet."
</blockquote>
<h3 class="text-2xl font-black font-display text-slate-900 dark:text-white mt-8 mb-4">How Our Newsroom Operates</h3>
<p class="mb-4 leading-relaxed text-slate-600 dark:text-slate-300">
    Our editorial desk functions as a collaborative workbench. High school and campus contributors pitch stories through our Assignment Center, collaborate directly with managing editors, and utilize modern tools—including Google Gemini AI assistant for research and headline discovery—while maintaining strict fact-checking and authentic human authorship.
</p>
<p class="mb-4 leading-relaxed text-slate-600 dark:text-slate-300">
    Zero corporate gatekeepers. No recycled press releases. Just sharp, unfiltered dispatches from the frontlines of youth culture.
</p>';

    $defaultMission = "Empowering the next generation of digital natives with fearless, fact-checked, and algorithm-defying storytelling. We bridge high school creators, tech disruptors, and cultural architects to cultivate a more thoughtful, expressive internet.";
    $defaultManifesto = "We believe media should respect your attention, not exploit it. We amplify authentic voices over corporate agendas, celebrate tactile creativity, and champion digital literacy that gives young minds real agency.";

    if (!$existing) {
        $insert = $pdo->prepare("
            INSERT INTO `pages` (`slug`, `title`, `subtitle`, `hero_image`, `content`, `mission`, `manifesto`, `stats_json`, `updated_by_user_id`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $insert->execute([
            'about',
            'About Brighteen',
            'The Unfiltered Digital Media Hub for Gen-Z & Gen-Alpha Culture, Tech, and Creative Expression.',
            'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1600&q=80',
            $defaultContent,
            $defaultMission,
            $defaultManifesto,
            $defaultStats
        ]);
        echo "Default About Us page seeded successfully.\n";
    } else {
        echo "About Us page already exists in `pages`.\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
