<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';

class Page extends Model
{
    protected static $table = 'pages';

    public static function findBySlug($slug)
    {
        $stmt = db()->prepare("SELECT p.*, u.name as editor_name FROM pages p LEFT JOIN users u ON p.updated_by_user_id = u.id WHERE p.slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['stats_json']) && is_string($row['stats_json'])) {
            $row['stats'] = json_decode($row['stats_json'], true) ?: [];
        } else {
            $row['stats'] = [];
        }
        return $row ?: null;
    }

    public static function getAbout()
    {
        $page = self::findBySlug('about');
        if ($page) {
            return $page;
        }

        // Return sensible default if not seeded yet
        return [
            'id' => null,
            'slug' => 'about',
            'title' => 'About Brighteen',
            'subtitle' => 'The Unfiltered Digital Media Hub for Gen-Z & Gen-Alpha Culture, Tech, and Creative Expression.',
            'hero_image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1600&q=80',
            'content' => '<p class="text-lg leading-relaxed mb-6 font-medium text-slate-700 dark:text-slate-300">Brighteen is an independent digital media platform empowering Gen-Z & Gen-Alpha creators, journalists, and cultural curators.</p>',
            'mission' => 'Empowering the next generation of digital natives with fearless, fact-checked, and algorithm-defying storytelling.',
            'manifesto' => 'We believe media should respect your attention, not exploit it. We amplify authentic voices over corporate agendas.',
            'stats' => [
                ["value" => "150K+", "label" => "Monthly Youth Readers", "icon" => "trending_up"],
                ["value" => "2,400+", "label" => "Campus Dispatches", "icon" => "auto_stories"],
                ["value" => "85+", "label" => "Student Reporters", "icon" => "groups"],
                ["value" => "100%", "label" => "Independent & Youth-Led", "icon" => "verified"]
            ],
            'editor_name' => 'Editorial Team'
        ];
    }

    public static function updateAbout(array $data, $userId)
    {
        $existing = self::findBySlug('about');
        
        $fields = [
            'title' => trim($data['title'] ?? 'About Brighteen'),
            'subtitle' => trim($data['subtitle'] ?? ''),
            'hero_image' => trim($data['hero_image'] ?? ''),
            'content' => $data['content'] ?? '',
            'mission' => trim($data['mission'] ?? ''),
            'manifesto' => trim($data['manifesto'] ?? ''),
            'stats_json' => is_string($data['stats_json'] ?? null) ? $data['stats_json'] : json_encode($data['stats'] ?? []),
            'updated_by_user_id' => (int)$userId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing && !empty($existing['id'])) {
            self::update($existing['id'], $fields);
            return $existing['id'];
        } else {
            $fields['slug'] = 'about';
            $fields['created_at'] = date('Y-m-d H:i:s');
            return self::create($fields);
        }
    }
}
