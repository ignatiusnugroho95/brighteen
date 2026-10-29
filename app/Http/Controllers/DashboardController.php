<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\Assignment;
use PDO;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Assignment.php';

class DashboardController
{
    private function checkAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=dashboard');
        }
    }

    public function index()
    {
        $this->checkAccess();

        $userId = auth()->id();
        $userRole = auth()->role();

        // 1. Core Statistics
        // A. Jumlah artikel yang pernah terbit
        $stmt = db()->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        $totalPublishedArticles = (int)$stmt->fetchColumn();

        // B. Jumlah rubrik (categories)
        $stmt = db()->query("SELECT COUNT(*) FROM categories");
        $totalCategories = (int)$stmt->fetchColumn();

        // C. Jumlah views total
        $stmt = db()->query("SELECT COALESCE(SUM(views_count), 0) FROM articles WHERE status = 'published'");
        $totalViews = (int)$stmt->fetchColumn();

        // D. Jumlah menit yang bisa dihabiskan untuk reading
        // Reading speed standard for youth/students is ~200 words per minute (WPM)
        $stmt = db()->query("SELECT content FROM articles WHERE status = 'published'");
        $publishedContents = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $totalWordCount = 0;
        foreach ($publishedContents as $content) {
            $cleanText = strip_tags($content);
            $totalWordCount += str_word_count($cleanText);
        }
        $totalReadingMinutes = max(1, (int)ceil($totalWordCount / 200));

        // E. Jumlah interaksi (likes, saves, comments, shares)
        $stmt = db()->query("SELECT COUNT(*) FROM likes");
        $totalLikes = (int)$stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM saved_posts");
        $totalSaves = (int)$stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM comments");
        $totalComments = (int)$stmt->fetchColumn();

        // Estimated social shares based on reading activity
        $totalShares = (int)ceil($totalLikes * 2.5 + $totalComments * 0.8);
        $totalInteractions = $totalLikes + $totalSaves + $totalComments + $totalShares;
        $engagementRate = $totalViews > 0 ? round(($totalInteractions / $totalViews) * 100, 2) : 0;

        // F. Jumlah reader (registered readers)
        $stmt = db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'reader_registered'");
        $totalRegisteredReaders = (int)$stmt->fetchColumn();

        // Total community members (all registered users)
        $stmt = db()->query("SELECT COUNT(*) FROM users");
        $totalCommunityUsers = (int)$stmt->fetchColumn();

        // G. Jumlah contributor (community contributors)
        $stmt = db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'contributor'");
        $totalContributors = (int)$stmt->fetchColumn();

        // Total journalists
        $stmt = db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'journalist'");
        $totalJournalists = (int)$stmt->fetchColumn();

        // Total editorial team
        $stmt = db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('editor', 'management')");
        $totalEditors = (int)$stmt->fetchColumn();

        // 2. Top 5 Views Artikel
        $sqlTopViews = "
            SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.views_count, a.published_at,
                   c.name as category_name, c.color_code as category_color, c.icon as category_icon,
                   u.name as author_name, u.avatar as author_avatar
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.status = 'published'
            ORDER BY a.views_count DESC
            LIMIT 5
        ";
        $topViewsArticles = db()->query($sqlTopViews)->fetchAll(PDO::FETCH_ASSOC);

        // 3. Top 5 Most Liked Artikel
        $sqlTopLiked = "
            SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.views_count, a.published_at,
                   c.name as category_name, c.color_code as category_color, c.icon as category_icon,
                   u.name as author_name, u.avatar as author_avatar,
                   COUNT(l.id) as likes_count
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            LEFT JOIN likes l ON l.article_id = a.id
            WHERE a.status = 'published'
            GROUP BY a.id, a.title, a.slug, a.excerpt, a.featured_image, a.views_count, a.published_at, c.name, c.color_code, c.icon, u.name, u.avatar
            ORDER BY likes_count DESC, a.views_count DESC
            LIMIT 5
        ";
        $topLikedArticles = db()->query($sqlTopLiked)->fetchAll(PDO::FETCH_ASSOC);

        // 4. Interactive Comparative & Progressive Data
        // A. Category distribution (Comparative)
        $sqlCategories = "
            SELECT c.id, c.name, c.slug, c.color_code, c.icon,
                   COUNT(a.id) as article_count,
                   COALESCE(SUM(a.views_count), 0) as total_views
            FROM categories c
            LEFT JOIN articles a ON a.category_id = c.id AND a.status = 'published'
            GROUP BY c.id, c.name, c.slug, c.color_code, c.icon
            ORDER BY article_count DESC, total_views DESC
        ";
        $categoryStats = db()->query($sqlCategories)->fetchAll(PDO::FETCH_ASSOC);

        // Calculate percentage for each category
        $maxCatViews = 1;
        foreach ($categoryStats as &$cat) {
            if ((int)$cat['total_views'] > $maxCatViews) {
                $maxCatViews = (int)$cat['total_views'];
            }
        }
        unset($cat);
        foreach ($categoryStats as &$cat) {
            $cat['views_percent'] = $totalViews > 0 ? round(((int)$cat['total_views'] / $totalViews) * 100, 1) : 0;
            $cat['article_percent'] = $totalPublishedArticles > 0 ? round(((int)$cat['article_count'] / $totalPublishedArticles) * 100, 1) : 0;
        }
        unset($cat);

        // B. Editorial Pipeline Health (Status comparison)
        $stmt = db()->query("SELECT status, COUNT(*) as count FROM articles GROUP BY status");
        $articleStatusCounts = [
            'published' => 0,
            'pending_review' => 0,
            'draft' => 0,
            'rejected' => 0
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $articleStatusCounts[$row['status']] = (int)$row['count'];
        }

        // Active commission assignments
        $stmt = db()->query("SELECT COUNT(*) FROM assignments WHERE status IN ('pending', 'turned_in')");
        $activeAssignmentsCount = (int)$stmt->fetchColumn();

        // C. Progressive publication timeline
        $sqlTimeline = "
            SELECT DATE(published_at) as pub_date, COUNT(*) as articles_count, SUM(views_count) as views_count
            FROM articles
            WHERE status = 'published' AND published_at IS NOT NULL
            GROUP BY DATE(published_at)
            ORDER BY pub_date ASC
        ";
        $timelineData = db()->query($sqlTimeline)->fetchAll(PDO::FETCH_ASSOC);

        // D. Author / Contributor Productivity Leaderboard
        $sqlAuthors = "
            SELECT u.id, u.name, u.avatar, r.name as role_name, r.display_name as role_display_name,
                   COUNT(a.id) as published_count,
                   COALESCE(SUM(a.views_count), 0) as total_views,
                   (SELECT COUNT(*) FROM likes l JOIN articles art ON l.article_id = art.id WHERE art.user_id = u.id) as total_received_likes
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN articles a ON a.user_id = u.id AND a.status = 'published'
            WHERE r.name IN ('management', 'editor', 'journalist', 'contributor')
            GROUP BY u.id, u.name, u.avatar, r.name, r.display_name
            ORDER BY published_count DESC, total_views DESC
            LIMIT 8
        ";
        $authorLeaderboard = db()->query($sqlAuthors)->fetchAll(PDO::FETCH_ASSOC);

        // 5. Shortcuts Definition for Each Role
        $roleShortcuts = $this->getRoleShortcuts();

        return view('dashboard.index', [
            'currentRole' => $userRole,
            'currentUser' => auth()->user(),
            'totalPublishedArticles' => $totalPublishedArticles,
            'totalCategories' => $totalCategories,
            'totalViews' => $totalViews,
            'totalReadingMinutes' => $totalReadingMinutes,
            'totalWordCount' => $totalWordCount,
            'totalLikes' => $totalLikes,
            'totalSaves' => $totalSaves,
            'totalComments' => $totalComments,
            'totalShares' => $totalShares,
            'totalInteractions' => $totalInteractions,
            'engagementRate' => $engagementRate,
            'totalRegisteredReaders' => $totalRegisteredReaders,
            'totalCommunityUsers' => $totalCommunityUsers,
            'totalContributors' => $totalContributors,
            'totalJournalists' => $totalJournalists,
            'totalEditors' => $totalEditors,
            'topViewsArticles' => $topViewsArticles,
            'topLikedArticles' => $topLikedArticles,
            'categoryStats' => $categoryStats,
            'articleStatusCounts' => $articleStatusCounts,
            'activeAssignmentsCount' => $activeAssignmentsCount,
            'timelineData' => $timelineData,
            'authorLeaderboard' => $authorLeaderboard,
            'roleShortcuts' => $roleShortcuts
        ]);
    }

    /**
     * Define comprehensive shortcuts tailored to each staff & creator role
     */
    private function getRoleShortcuts()
    {
        return [
            'management' => [
                'title' => 'Management Portal',
                'badge' => 'Platform Director',
                'badge_color' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                'desc' => 'Akses kontrol penuh ke tata kelola staf, administrasi pembaca, pengaturan redaksi, dan analitik publikasi.',
                'items' => [
                    [
                        'title' => 'Platform Management Panel',
                        'desc' => 'Kelola akun staf redaksi, peran pengguna, pengaturan sistem & profil sekolah.',
                        'url' => 'dashboard/management',
                        'icon' => 'admin_panel_settings',
                        'color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
                    ],
                    [
                        'title' => 'Reader Administration',
                        'desc' => 'Daftar akun pembaca terdaftar dan verifikasi aplikasi pengajuan kontributor siswa.',
                        'url' => 'dashboard/management#readers',
                        'icon' => 'how_to_reg',
                        'color' => 'text-purple-500 bg-purple-500/10 border-purple-500/20'
                    ],
                    [
                        'title' => 'Editorial Review Desk',
                        'desc' => 'Review antrean naskah masuk, kurasi headline utama, kelola rubrik & pengumuman.',
                        'url' => 'dashboard/editorial',
                        'icon' => 'rule',
                        'color' => 'text-purple-600 bg-purple-600/10 border-purple-600/20'
                    ],
                    [
                        'title' => 'Editorial Assignment Center',
                        'desc' => 'Pantau penugasan liputan jurnalis, tenggat waktu, dan brief liputan siswa.',
                        'url' => 'dashboard/assignments',
                        'icon' => 'assignment',
                        'color' => 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20'
                    ],
                    [
                        'title' => 'Write Article with Gemini AI',
                        'desc' => 'Tulis artikel atau berita baru didukung AI Assistant dengan persona majalah SMP.',
                        'url' => 'articles/create',
                        'icon' => 'edit_square',
                        'color' => 'text-primary bg-primary/10 border-primary/20'
                    ],
                    [
                        'title' => 'Edit About Us & Vision',
                        'desc' => 'Perbarui manifesto, visi misi, statistik sekolah, dan formulir ajakan kontributor.',
                        'url' => 'about/edit',
                        'icon' => 'info',
                        'color' => 'text-amber-500 bg-amber-500/10 border-amber-500/20'
                    ],
                    [
                        'title' => 'Live Magazine Edition',
                        'desc' => 'Buka beranda publikasi live untuk melihat tampilan yang dinikmati pembaca.',
                        'url' => '',
                        'icon' => 'dynamic_feed',
                        'color' => 'text-sky-500 bg-sky-500/10 border-sky-500/20'
                    ],
                    [
                        'title' => 'Reading Bookmarks',
                        'desc' => 'Daftar koleksi bacaan dan artikel yang disimpan oleh akun Anda.',
                        'url' => 'bookmarks',
                        'icon' => 'bookmark',
                        'color' => 'text-rose-500 bg-rose-500/10 border-rose-500/20'
                    ]
                ]
            ],
            'editor' => [
                'title' => 'Editorial Workspace',
                'badge' => 'Senior Managing Editor',
                'badge_color' => 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30',
                'desc' => 'Pusat kendali redaksi untuk menilai kualitas naskah, menetapkan headline, kurasi rubrik, dan penugasan jurnalis.',
                'items' => [
                    [
                        'title' => 'Editorial Review Queue',
                        'desc' => 'Evaluasi, beri masukan perbaikan, atau setujui artikel yang dikirim jurnalis/kontributor.',
                        'url' => 'dashboard/editorial#review',
                        'icon' => 'rate_review',
                        'color' => 'text-purple-500 bg-purple-500/10 border-purple-500/20'
                    ],
                    [
                        'title' => 'Headline & Curated Stories',
                        'desc' => 'Pilih naskah unggulan untuk tayang sebagai headline utama beranda dan rubrik pilihan.',
                        'url' => 'dashboard/editorial#curated',
                        'icon' => 'star',
                        'color' => 'text-amber-500 bg-amber-500/10 border-amber-500/20'
                    ],
                    [
                        'title' => 'Manage Rubrics & Categories',
                        'desc' => 'Kelola tema rubrik majalah, ubah warna aksen, ikon, dan deskripsi rubrik.',
                        'url' => 'dashboard/editorial#categories',
                        'icon' => 'category',
                        'color' => 'text-sky-500 bg-sky-500/10 border-sky-500/20'
                    ],
                    [
                        'title' => 'Commission New Assignment',
                        'desc' => 'Buat brief liputan baru dan tugaskan ke jurnalis tertentu dengan tenggat waktu.',
                        'url' => 'dashboard/assignments',
                        'icon' => 'add_task',
                        'color' => 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20'
                    ],
                    [
                        'title' => 'Write & Direct Publish',
                        'desc' => 'Editor dapat langsung menerbitkan artikel secara instan atau menyimpannya sebagai draf.',
                        'url' => 'articles/create',
                        'icon' => 'edit_document',
                        'color' => 'text-primary bg-primary/10 border-primary/20'
                    ],
                    [
                        'title' => 'Edit About Us & Manifesto',
                        'desc' => 'Ubah profil redaksi, teks sambutan pimpinan, dan panduan gaya selingkung.',
                        'url' => 'about/edit',
                        'icon' => 'menu_book',
                        'color' => 'text-amber-600 bg-amber-600/10 border-amber-600/20'
                    ],
                    [
                        'title' => 'My Drafts & Submitted Works',
                        'desc' => 'Pantau draf penulisan dan karya Anda sendiri di meja penugasan.',
                        'url' => 'dashboard/assignments#drafts',
                        'icon' => 'note_alt',
                        'color' => 'text-teal-500 bg-teal-500/10 border-teal-500/20'
                    ],
                    [
                        'title' => 'Live Feed Preview',
                        'desc' => 'Lihat tata letak live publikasi di sisi pembaca.',
                        'url' => '',
                        'icon' => 'visibility',
                        'color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
                    ]
                ]
            ],
            'journalist' => [
                'title' => 'Journalist Reporter Desk',
                'badge' => 'Staff Journalist',
                'badge_color' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30',
                'desc' => 'Meja kerja jurnalis staf untuk menerima penugasan liputan, menyusun draf cerita, dan memantau status publikasi.',
                'items' => [
                    [
                        'title' => 'Active Assigned Briefs',
                        'desc' => 'Lihat penugasan liputan aktif dari editor, batas waktu pengumpulan, dan instruksi.',
                        'url' => 'dashboard/assignments#active',
                        'icon' => 'assignment_turned_in',
                        'color' => 'text-amber-500 bg-amber-500/10 border-amber-500/20'
                    ],
                    [
                        'title' => 'Saved Drafts (WIP)',
                        'desc' => 'Lanjutkan penulisan draf cerita Anda yang belum disubmit atau perlu revisi.',
                        'url' => 'dashboard/assignments#drafts',
                        'icon' => 'edit_note',
                        'color' => 'text-sky-500 bg-sky-500/10 border-sky-500/20'
                    ],
                    [
                        'title' => 'In Editorial Review',
                        'desc' => 'Pantau artikel yang sudah Anda submit dan sedang menunggu pemeriksaan editor.',
                        'url' => 'dashboard/assignments#review',
                        'icon' => 'hourglass_top',
                        'color' => 'text-purple-500 bg-purple-500/10 border-purple-500/20'
                    ],
                    [
                        'title' => 'Write New Story with AI',
                        'desc' => 'Buka meja penulisan dengan bantuan riset 5W+1H dan kutipan wawancara otomatis.',
                        'url' => 'articles/create',
                        'icon' => 'draw',
                        'color' => 'text-primary bg-primary/10 border-primary/20'
                    ],
                    [
                        'title' => 'My Published Portfolio',
                        'desc' => 'Koleksi seluruh artikel karya Anda yang telah sukses diterbitkan redaksi.',
                        'url' => 'dashboard/assignments#published',
                        'icon' => 'verified',
                        'color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
                    ],
                    [
                        'title' => 'Bookmarks & Saved Lore',
                        'desc' => 'Buka artikel referensi yang pernah Anda simpan untuk bahan riset berita.',
                        'url' => 'bookmarks',
                        'icon' => 'bookmark',
                        'color' => 'text-rose-500 bg-rose-500/10 border-rose-500/20'
                    ]
                ]
            ],
            'contributor' => [
                'title' => 'Contributor Creative Corner',
                'badge' => 'Community Contributor',
                'badge_color' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30',
                'desc' => 'Ruang kreasi kontributor siswa untuk mengirimkan opini, cerita pendek, karya seni, dan liputan mandiri.',
                'items' => [
                    [
                        'title' => 'Write New Pitch / Story',
                        'desc' => 'Mulai menulis karya baru, kembangkan ide dengan AI Assistant, dan submit ke dewan redaksi.',
                        'url' => 'articles/create',
                        'icon' => 'stylus',
                        'color' => 'text-primary bg-primary/10 border-primary/20'
                    ],
                    [
                        'title' => 'My Saved Drafts',
                        'desc' => 'Simpan draf tulisan sementara dan perbaiki catatan masukan dari editor.',
                        'url' => 'dashboard/assignments#drafts',
                        'icon' => 'drafts',
                        'color' => 'text-sky-500 bg-sky-500/10 border-sky-500/20'
                    ],
                    [
                        'title' => 'Stories Under Review',
                        'desc' => 'Periksa status tulisan Anda yang sedang dibaca dan dievaluasi oleh editor.',
                        'url' => 'dashboard/assignments#review',
                        'icon' => 'pending_actions',
                        'color' => 'text-purple-500 bg-purple-500/10 border-purple-500/20'
                    ],
                    [
                        'title' => 'Published Articles Portfolio',
                        'desc' => 'Lihat karya Anda yang sudah tayang di majalah Brighteen beserta statistik pembacanya.',
                        'url' => 'dashboard/assignments#published',
                        'icon' => 'auto_awesome',
                        'color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
                    ],
                    [
                        'title' => 'Contributor Lore & Guidelines',
                        'desc' => 'Pelajari panduan menulis artikel remaja dan kriteria tulisan yang disukai pembaca.',
                        'url' => 'about',
                        'icon' => 'lightbulb',
                        'color' => 'text-amber-500 bg-amber-500/10 border-amber-500/20'
                    ],
                    [
                        'title' => 'Reading Bookmarks',
                        'desc' => 'Buka kembali artikel majalah yang Anda simpan untuk dibaca kembali.',
                        'url' => 'bookmarks',
                        'icon' => 'bookmark',
                        'color' => 'text-rose-500 bg-rose-500/10 border-rose-500/20'
                    ]
                ]
            ],
            'reader_registered' => [
                'title' => 'Student Reader Hub',
                'badge' => 'Registered Reader',
                'badge_color' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30',
                'desc' => 'Ruang membaca interaktif untuk menjelajah berita sekolah, menyimpan artikel favorit, dan bergabung menjadi kontributor.',
                'items' => [
                    [
                        'title' => 'Explore Live Magazine',
                        'desc' => 'Baca berita terbaru seputar teknologi, seni, gaya hidup, dan kegiatan sekolah.',
                        'url' => '',
                        'icon' => 'newspaper',
                        'color' => 'text-primary bg-primary/10 border-primary/20'
                    ],
                    [
                        'title' => 'My Bookmarks List',
                        'desc' => 'Daftar seluruh cerita dan artikel yang telah Anda bookmark untuk dibaca nanti.',
                        'url' => 'bookmarks',
                        'icon' => 'bookmark',
                        'color' => 'text-rose-500 bg-rose-500/10 border-rose-500/20'
                    ],
                    [
                        'title' => 'Apply to Become Contributor',
                        'desc' => 'Daftarkan diri Anda untuk mulai menulis karya sendiri dan terbit di majalah Brighteen!',
                        'url' => 'about#apply',
                        'icon' => 'how_to_reg',
                        'color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
                    ],
                    [
                        'title' => 'About Brighteen Vision',
                        'desc' => 'Ketahui lebih banyak tentang visi majalah siswa SMP Brighteen.',
                        'url' => 'about',
                        'icon' => 'info',
                        'color' => 'text-sky-500 bg-sky-500/10 border-sky-500/20'
                    ]
                ]
            ]
        ];
    }
}
