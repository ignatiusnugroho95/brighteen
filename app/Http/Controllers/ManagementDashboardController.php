<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContributorApplication;
use App\Models\Notification;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/ContributorApplication.php';
require_once __DIR__ . '/../../Models/Notification.php';
require_once __DIR__ . '/../../Models/Role.php';
require_once __DIR__ . '/../../Models/SystemSetting.php';
require_once __DIR__ . '/../../Models/User.php';

class ManagementDashboardController
{
    private function checkManagementAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=dashboard/management');
        }

        if (!auth()->isManagement()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Platform Management permissions required.']));
        }
    }

    public function index()
    {
        $this->checkManagementAccess();

        // Platform Metrics
        $totalArticles = Article::count();
        $publishedCount = Article::count("status = 'published'");
        $pendingCount = Article::count("status = 'pending_review'");
        $draftCount = Article::count("status = 'draft'");
        $totalUsers = User::count();
        
        $viewsResult = db()->query("SELECT SUM(views_count) as total_views FROM articles")->fetch();
        $totalViews = (int)($viewsResult['total_views'] ?? 0);

        $commentsResult = db()->query("SELECT COUNT(*) as total_comments FROM comments")->fetch();
        $totalComments = (int)($commentsResult['total_comments'] ?? 0);

        $likesResult = db()->query("SELECT COUNT(*) as total_likes FROM likes")->fetch();
        $totalLikes = (int)($likesResult['total_likes'] ?? 0);

        // Separate Staff / Content Creators from Registered Readers
        $staffUsers = User::staffWithRoles();
        $readers = User::readersWithRoles();
        $applications = ContributorApplication::allWithUsers();
        $pendingApplicationsCount = ContributorApplication::pendingCount();

        $roles = Role::all('id ASC');
        $allArticles = Article::all('id DESC');
        $allSettings = SystemSetting::all('id ASC');
        $settings = array_values(array_filter($allSettings, function ($st) {
            return !in_array($st['key'], ['announcement_text', 'announcements_migrated', 'headline_article_id', 'curated_article_ids'], true);
        }));

        return view('dashboard.management', [
            'metrics' => [
                'total_articles' => $totalArticles,
                'published' => $publishedCount,
                'pending' => $pendingCount,
                'drafts' => $draftCount,
                'total_users' => $totalUsers,
                'total_staff' => count($staffUsers),
                'total_readers' => count($readers),
                'pending_applications' => $pendingApplicationsCount,
                'total_views' => $totalViews,
                'total_comments' => $totalComments,
                'total_likes' => $totalLikes,
            ],
            'staffUsers' => $staffUsers,
            'readers' => $readers,
            'applications' => $applications,
            'pendingApplicationsCount' => $pendingApplicationsCount,
            'roles' => $roles,
            'articles' => $allArticles,
            'settings' => $settings,
            'notice' => $_GET['notice'] ?? null
        ]);
    }

    public function updateUserRole($id)
    {
        $this->checkManagementAccess();

        $roleId = (int)($_POST['role_id'] ?? 0);
        if ($roleId > 0) {
            $oldUser = User::find($id);
            User::update($id, ['role_id' => $roleId]);

            // If user is promoted to Contributor (role_id = 4), send notification
            if ($roleId === 4 && $oldUser && (int)$oldUser['role_id'] !== 4) {
                Notification::send(
                    $id,
                    'assignment_promoted',
                    '🎉 You are now a Contributor!',
                    'Congratulations! Management has approved your contributor role. You can now access the Writer Workbench and publish story drafts.',
                    'articles/create'
                );
            }
        }

        $source = $_POST['source'] ?? 'users';
        redirect('dashboard/management?notice=role_updated#' . ($source === 'readers' ? 'readers' : 'users'));
    }

    public function approveApplication($id)
    {
        $this->checkManagementAccess();
        ContributorApplication::approve($id, auth()->id());
        redirect('dashboard/management?notice=application_approved#readers');
    }

    public function rejectApplication($id)
    {
        $this->checkManagementAccess();
        $reason = trim($_POST['rejection_reason'] ?? '');
        ContributorApplication::reject($id, auth()->id(), $reason ?: null);
        redirect('dashboard/management?notice=application_rejected#readers');
    }

    public function updateSettings()
    {
        $this->checkManagementAccess();

        if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $val) {
                if (in_array($key, ['announcement_text', 'announcements_migrated'], true)) {
                    continue;
                }
                SystemSetting::set($key, $val);
            }
        }

        redirect('dashboard/management?notice=settings_saved#settings');
    }

    public function deleteArticle($id)
    {
        $this->checkManagementAccess();
        Article::delete($id);
        redirect('dashboard/management?notice=article_deleted');
    }
}
