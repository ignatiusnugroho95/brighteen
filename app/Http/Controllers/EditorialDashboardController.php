<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Assignment;
use App\Models\User;
use App\Models\SystemSetting;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/Assignment.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/SystemSetting.php';

class EditorialDashboardController
{
    private function checkEditorAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=dashboard/editorial');
        }

        if (!auth()->isEditor() && !auth()->isManagement()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Editorial permissions required.']));
        }
    }

    public function index()
    {
        $this->checkEditorAccess();

        // Run automatic deadline and publishing reminders
        Assignment::checkAndGenerateReminders();

        $pendingArticles = Article::getPendingReview();
        $publishedArticles = Article::getPublished(40);
        $categories = Category::allWithArticleCount();

        // Active Headline management
        $headlineArticleId = (int)SystemSetting::get('headline_article_id', 0);
        $currentHeadline = null;
        if ($headlineArticleId > 0) {
            $currentHeadline = Article::findWithDetails($headlineArticleId);
        }
        if (!$currentHeadline) {
            $stmtH = db()->query("SELECT id FROM articles WHERE is_headline = 1 AND status = 'published' LIMIT 1");
            $hId = (int)$stmtH->fetchColumn();
            if ($hId > 0) {
                $headlineArticleId = $hId;
                $currentHeadline = Article::findWithDetails($headlineArticleId);
            }
        }
        if (!$currentHeadline) {
            $defaultArt = db()->query("SELECT * FROM articles WHERE status = 'published' ORDER BY is_headline DESC, published_at DESC LIMIT 1")->fetch();
            if ($defaultArt) {
                $currentHeadline = Article::findWithDetails($defaultArt['id']);
                $headlineArticleId = (int)$defaultArt['id'];
                SystemSetting::set('headline_article_id', (string)$headlineArticleId, 'Active headline article ID on homepage hero');
                db()->exec("UPDATE articles SET is_headline = 1 WHERE id = {$headlineArticleId}");
            }
        }

        $assignments = Assignment::allDetailed();
        $journalistsStmt = db()->query("
            SELECT u.id, u.name, u.email, u.avatar, r.name as role_name, r.display_name as role_display_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name IN ('journalist', 'contributor', 'editor') 
            ORDER BY u.name ASC
        ");
        $journalists = $journalistsStmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalPending = count($pendingArticles);
        $totalPublished = Article::count("status = 'published'");
        $totalDrafts = Article::count("status = 'draft'");
        $totalAssignments = count($assignments);

        // Active Curated Stories management (4 stories featured at a time)
        $curatedSetting = SystemSetting::get('curated_article_ids', '[]');
        $curatedArticleIds = json_decode($curatedSetting, true);
        if (!is_array($curatedArticleIds) || empty($curatedArticleIds)) {
            $stmtC = db()->query("SELECT id FROM articles WHERE is_curated = 1 AND status = 'published' ORDER BY published_at DESC LIMIT 4");
            $curatedArticleIds = array_map('intval', $stmtC->fetchAll(\PDO::FETCH_COLUMN));
            if (empty($curatedArticleIds)) {
                $stmtC2 = db()->prepare("SELECT id FROM articles WHERE status = 'published' AND id != ? ORDER BY published_at DESC LIMIT 4");
                $stmtC2->execute([$headlineArticleId]);
                $curatedArticleIds = array_map('intval', $stmtC2->fetchAll(\PDO::FETCH_COLUMN));
            }
            SystemSetting::set('curated_article_ids', json_encode($curatedArticleIds), 'Active 4 curated school story IDs on homepage');
        } else {
            $curatedArticleIds = array_values(array_map('intval', $curatedArticleIds));
        }

        return view('dashboard.editorial', [
            'pendingArticles' => $pendingArticles,
            'publishedArticles' => $publishedArticles,
            'categories' => $categories,
            'assignments' => $assignments,
            'journalists' => $journalists,
            'totalPending' => $totalPending,
            'totalPublished' => $totalPublished,
            'totalDrafts' => $totalDrafts,
            'totalAssignments' => $totalAssignments,
            'headlineArticleId' => $headlineArticleId,
            'currentHeadline' => $currentHeadline,
            'curatedArticleIds' => $curatedArticleIds,
            'notice' => $_GET['notice'] ?? null,
            'headlineId' => $_GET['headline_id'] ?? null,
            'curatedId' => $_GET['curated_id'] ?? null,
            'categoryName' => $_GET['category_name'] ?? null,
            'categoryColor' => $_GET['category_color'] ?? null,
            'categoryIcon' => $_GET['category_icon'] ?? null,
            'categorySlug' => $_GET['category_slug'] ?? null,
            'deletedName' => $_GET['deleted_name'] ?? null,
            'targetName' => $_GET['target_name'] ?? null,
            'movedCount' => isset($_GET['moved_count']) ? (int)$_GET['moved_count'] : 0,
            'error' => $_GET['error'] ?? null
        ]);
    }

    public function approve($id)
    {
        $this->checkEditorAccess();

        $article = Article::find($id);
        if (!$article) {
            redirect('dashboard/editorial?error=article_not_found');
        }

        Article::update($id, [
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'editorial_feedback' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Sync assignment status to approved if linked
        $linkedAssignment = Assignment::findBy('article_id', $id);
        if ($linkedAssignment) {
            Assignment::update($linkedAssignment['id'], [
                'status' => 'approved',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        redirect('dashboard/editorial?notice=article_approved');
    }

    public function reject($id)
    {
        $this->checkEditorAccess();

        $article = Article::find($id);
        if (!$article) {
            redirect('dashboard/editorial?error=article_not_found');
        }

        $feedback = trim($_POST['feedback'] ?? 'Please revise according to our editorial standards.');

        Article::update($id, [
            'status' => 'rejected',
            'editorial_feedback' => $feedback,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        redirect('dashboard/editorial?notice=article_rejected');
    }

    public function promoteHeadline($id)
    {
        $this->checkEditorAccess();

        $article = Article::find($id);
        if (!$article) {
            redirect('dashboard/editorial?error=article_not_found');
        }

        if ($article['status'] !== 'published') {
            redirect('dashboard/editorial?error=article_not_published');
        }

        $id = (int)$id;

        // Only one headline promoted at a time
        // 1. Reset all articles is_headline to 0
        db()->query("UPDATE articles SET is_headline = 0");

        // 2. Set this article is_headline to 1
        $stmt = db()->prepare("UPDATE articles SET is_headline = 1 WHERE id = ?");
        $stmt->execute([$id]);

        // 3. Persist into system_settings
        SystemSetting::set('headline_article_id', (string)$id, 'Active headline article ID on homepage hero');

        // 4. If this article was previously in the curated list, remove it from curated stories
        $curatedSetting = SystemSetting::get('curated_article_ids', '[]');
        $curatedIds = json_decode($curatedSetting, true);
        if (is_array($curatedIds) && in_array($id, array_map('intval', $curatedIds), true)) {
            $curatedIds = array_values(array_filter(array_map('intval', $curatedIds), function($cId) use ($id) {
                return $cId !== $id;
            }));
            SystemSetting::set('curated_article_ids', json_encode($curatedIds), 'Active 4 curated school story IDs on homepage');
        }
        $stmtCurated = db()->prepare("UPDATE articles SET is_curated = 0 WHERE id = ?");
        $stmtCurated->execute([$id]);

        redirect('dashboard/editorial?notice=headline_promoted&headline_id=' . $id . '#dispatches');
    }

    public function promoteCurated($id)
    {
        $this->checkEditorAccess();

        $article = Article::find($id);
        if (!$article) {
            redirect('dashboard/editorial?error=article_not_found');
        }

        if ($article['status'] !== 'published') {
            redirect('dashboard/editorial?error=article_not_published');
        }

        $id = (int)$id;

        // If this article was currently the cover headline, clear headline
        $headlineId = (int)SystemSetting::get('headline_article_id', 0);
        if ($headlineId === $id) {
            SystemSetting::set('headline_article_id', '0', 'Active headline article ID on homepage hero');
            db()->exec("UPDATE articles SET is_headline = 0 WHERE id = {$id}");
        }

        // Retrieve current curated list
        $curatedSetting = SystemSetting::get('curated_article_ids', '[]');
        $curatedIds = json_decode($curatedSetting, true);
        if (!is_array($curatedIds)) {
            $curatedIds = [];
        }
        $curatedIds = array_values(array_map('intval', $curatedIds));

        // Add to curated list if not already present
        if (!in_array($id, $curatedIds, true)) {
            // Keep exactly 4 at a time: if already 4, shift out the oldest
            while (count($curatedIds) >= 4) {
                array_shift($curatedIds);
            }
            $curatedIds[] = $id;
        }

        // Save updated curated list
        SystemSetting::set('curated_article_ids', json_encode($curatedIds), 'Active 4 curated school story IDs on homepage');

        // Synchronize is_curated flag in articles table
        db()->query("UPDATE articles SET is_curated = 0");
        if (!empty($curatedIds)) {
            $placeholders = implode(',', array_fill(0, count($curatedIds), '?'));
            $stmt = db()->prepare("UPDATE articles SET is_curated = 1 WHERE id IN ($placeholders)");
            $stmt->execute($curatedIds);
        }

        redirect('dashboard/editorial?notice=curated_promoted&curated_id=' . $id . '#dispatches');
    }

    public function demoteCurated($id)
    {
        $this->checkEditorAccess();

        $id = (int)$id;
        $curatedSetting = SystemSetting::get('curated_article_ids', '[]');
        $curatedIds = json_decode($curatedSetting, true);
        if (!is_array($curatedIds)) {
            $curatedIds = [];
        }
        $curatedIds = array_values(array_map('intval', $curatedIds));

        $curatedIds = array_values(array_filter($curatedIds, function($cId) use ($id) {
            return $cId !== $id;
        }));

        SystemSetting::set('curated_article_ids', json_encode($curatedIds), 'Active 4 curated school story IDs on homepage');

        $stmt = db()->prepare("UPDATE articles SET is_curated = 0 WHERE id = ?");
        $stmt->execute([$id]);

        redirect('dashboard/editorial?notice=curated_demoted#dispatches');
    }

    public function storeCategory()
    {
        $this->checkEditorAccess();

        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color_code'] ?? '#2563eb');
        $icon = trim($_POST['icon'] ?? 'tag');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            redirect('dashboard/editorial?error=category_name_required');
        }

        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = $baseSlug ?: 'beat-' . time();
        $counter = 1;
        while (Category::findBy('slug', $slug)) {
            $slug = $baseSlug . '-' . (++$counter);
        }

        Category::create([
            'name' => $name,
            'slug' => $slug,
            'color_code' => $color,
            'icon' => $icon,
            'description' => $desc
        ]);

        $params = [
            'notice' => 'category_created',
            'category_name' => $name,
            'category_color' => $color,
            'category_icon' => $icon,
            'category_slug' => $slug
        ];

        redirect('dashboard/editorial?' . http_build_query($params));
    }

    public function updateCategory($id = null)
    {
        $this->checkEditorAccess();

        $id = $id ? (int)$id : (int)($_POST['id'] ?? $_POST['category_id'] ?? 0);
        $category = Category::find($id);
        if (!$category) {
            redirect('dashboard/editorial?error=category_not_found');
        }

        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color_code'] ?? '#2563eb');
        $icon = trim($_POST['icon'] ?? 'tag');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            redirect('dashboard/editorial?error=category_name_required');
        }

        $updateData = [
            'name' => $name,
            'color_code' => $color,
            'icon' => $icon,
            'description' => $desc
        ];

        $finalSlug = $category['slug'];
        if ($name !== $category['name']) {
            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $slug = $baseSlug ?: 'beat-' . time();
            $counter = 1;
            while (($existing = Category::findBy('slug', $slug)) && $existing['id'] != $id) {
                $slug = $baseSlug . '-' . (++$counter);
            }
            $updateData['slug'] = $slug;
            $finalSlug = $slug;
        }

        Category::update($id, $updateData);

        $params = [
            'notice' => 'category_updated',
            'category_name' => $name,
            'category_color' => $color,
            'category_icon' => $icon,
            'category_slug' => $finalSlug
        ];

        redirect('dashboard/editorial?' . http_build_query($params));
    }

    public function deleteCategory($id = null)
    {
        $this->checkEditorAccess();

        $id = $id ? (int)$id : (int)($_POST['id'] ?? $_POST['category_id'] ?? 0);
        $sourceCategory = Category::find($id);
        if (!$sourceCategory) {
            redirect('dashboard/editorial?error=category_not_found');
        }

        $targetCategoryId = (int)($_POST['target_category_id'] ?? 0);
        $targetCategory = Category::find($targetCategoryId);

        // Count total stories in the category being deleted
        $stmtCount = db()->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
        $stmtCount->execute([$id]);
        $movedCount = (int)$stmtCount->fetchColumn();

        if ($movedCount > 0 && (!$targetCategory || $targetCategoryId === (int)$id)) {
            redirect('dashboard/editorial?error=invalid_target_category');
        }

        // Reassign all stories to destination category before deleting
        if ($targetCategory && $targetCategoryId !== (int)$id && $movedCount > 0) {
            $stmtUpdate = db()->prepare("UPDATE articles SET category_id = ? WHERE category_id = ?");
            $stmtUpdate->execute([$targetCategoryId, $id]);
        }

        Category::delete($id);

        $params = [
            'notice' => 'category_deleted',
            'deleted_name' => $sourceCategory['name'],
            'target_name' => $targetCategory ? $targetCategory['name'] : 'Archived',
            'moved_count' => $movedCount
        ];

        redirect('dashboard/editorial?' . http_build_query($params));
    }
}
