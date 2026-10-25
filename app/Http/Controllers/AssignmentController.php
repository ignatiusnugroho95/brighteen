<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\User;
use App\Models\Notification;
use App\Models\Article;

require_once __DIR__ . '/../../Models/Assignment.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Notification.php';
require_once __DIR__ . '/../../Models/Article.php';

class AssignmentController
{
    private function checkAuth()
    {
        if (!auth()->check()) {
            redirect('login?redirect=dashboard/assignments');
        }

        // Open to all content creators (Contributors, Journalists, Editors, Management)
        if (!auth()->canCreateArticle()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Creator or editorial permissions required to access the writer workspace.']));
        }
    }

    private function checkEditorAccess()
    {
        $this->checkAuth();
        if (!auth()->isEditor() && !auth()->isManagement()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Editorial permissions required to manage assignments.']));
        }
    }

    /**
     * Journalist & Contributor Assignment and Draft Workspace
     * Accessible by Journalists, Contributors, and Editors
     */
    public function index()
    {
        $this->checkAuth();

        // Automatically trigger any due reminders dynamically
        Assignment::checkAndGenerateReminders();

        $userId = auth()->id();
        $assignments = Assignment::getForJournalist($userId);

        // Fetch author's own unpublished drafts (draft, pending_review, rejected)
        $myDrafts = Article::getUnpublishedByUser($userId);

        $totalDrafts = count($myDrafts);
        $draftOnlyCount = 0;
        $draftReviewCount = 0;
        $draftRejectedCount = 0;

        foreach ($myDrafts as $d) {
            if ($d['status'] === 'draft') {
                $draftOnlyCount++;
            } elseif ($d['status'] === 'pending_review') {
                $draftReviewCount++;
            } elseif ($d['status'] === 'rejected') {
                $draftRejectedCount++;
            }
        }

        // Metric counts for assignments
        $totalAssigned = count($assignments);
        $totalPending = 0;
        $totalTurnedIn = 0;
        $totalApproved = 0;
        $totalExpired = 0;

        $activeAssignments = [];
        $reviewAssignments = [];
        $publishedAssignments = [];

        foreach ($assignments as $a) {
            if ($a['effective_status'] === 'expired') {
                $totalExpired++;
                $activeAssignments[] = $a;
            } elseif ($a['effective_status'] === 'pending') {
                $totalPending++;
                $activeAssignments[] = $a;
            } elseif ($a['effective_status'] === 'turned_in') {
                $totalTurnedIn++;
                $reviewAssignments[] = $a;
            } elseif ($a['effective_status'] === 'approved') {
                $totalApproved++;
                $publishedAssignments[] = $a;
            }
        }

        // Collect article IDs that are already in reviewAssignments from assigned briefs
        $assignedArticleIds = [];
        foreach ($reviewAssignments as $ra) {
            if (!empty($ra['article_id'])) {
                $assignedArticleIds[] = (int)$ra['article_id'];
            }
        }

        // Also include all author's articles currently in pending_review that don't originate from an existing assigned brief
        foreach ($myDrafts as $d) {
            if ($d['status'] === 'pending_review' && !in_array((int)$d['id'], $assignedArticleIds)) {
                $totalTurnedIn++;
                $reviewAssignments[] = [
                    'id' => 'STORY-' . $d['id'],
                    'assignment_id' => $d['linked_assignment_id'] ?? null,
                    'topic' => $d['title'] ?: 'Independent Story Pitch',
                    'description' => $d['excerpt'] ?: '',
                    'category_id' => $d['category_id'],
                    'category_name' => $d['category_name'],
                    'category_color' => $d['category_color'],
                    'category_icon' => $d['category_icon'] ?? 'tag',
                    'editor_name' => 'Editorial Review Desk',
                    'article_id' => $d['id'],
                    'article_title' => $d['title'],
                    'article_slug' => $d['slug'],
                    'turned_in_at' => $d['updated_at'],
                    'effective_status' => 'turned_in',
                    'is_independent' => empty($d['linked_assignment_id'])
                ];
            }
        }

        $totalActive = $totalPending + $totalExpired;

        return view('dashboard.assignments', [
            'assignments' => $assignments,
            'activeAssignments' => $activeAssignments,
            'reviewAssignments' => $reviewAssignments,
            'publishedAssignments' => $publishedAssignments,
            'myDrafts' => $myDrafts,
            'totalDrafts' => $totalDrafts,
            'draftOnlyCount' => $draftOnlyCount,
            'draftReviewCount' => $draftReviewCount,
            'draftRejectedCount' => $draftRejectedCount,
            'totalAssigned' => $totalAssigned,
            'totalActive' => $totalActive,
            'totalPending' => $totalPending,
            'totalTurnedIn' => $totalTurnedIn,
            'totalApproved' => $totalApproved,
            'totalExpired' => $totalExpired,
            'notice' => $_GET['notice'] ?? null
        ]);
    }

    /**
     * Editor Assignment Creation
     */
    public function store()
    {
        $this->checkEditorAccess();

        $assignedTo = (int)($_POST['assigned_to_user_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $topic = trim($_POST['topic'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $submissionDeadline = trim($_POST['submission_deadline'] ?? '');
        $publishingSchedule = trim($_POST['publishing_schedule'] ?? '');

        if (!$assignedTo || !$categoryId || empty($topic) || empty($submissionDeadline) || empty($publishingSchedule)) {
            redirect('dashboard/editorial?error=missing_assignment_fields#assignments');
        }

        // Format dates into MySQL format if needed (e.g. from HTML5 datetime-local "YYYY-MM-DDTHH:MM")
        $submissionDeadlineFormatted = date('Y-m-d H:i:s', strtotime($submissionDeadline));
        $publishingScheduleFormatted = date('Y-m-d H:i:s', strtotime($publishingSchedule));

        Assignment::createWithNotification([
            'assigned_by_user_id' => auth()->id(),
            'assigned_to_user_id' => $assignedTo,
            'category_id' => $categoryId,
            'topic' => $topic,
            'description' => $description,
            'submission_deadline' => $submissionDeadlineFormatted,
            'publishing_schedule' => $publishingScheduleFormatted,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        redirect('dashboard/editorial?notice=assignment_created#assignments');
    }

    /**
     * Editor Assignment Update
     */
    public function update($id)
    {
        $this->checkEditorAccess();

        $assignment = Assignment::find($id);
        if (!$assignment) {
            redirect('dashboard/editorial?error=assignment_not_found#assignments');
        }

        $assignedTo = (int)($_POST['assigned_to_user_id'] ?? $assignment['assigned_to_user_id']);
        $categoryId = (int)($_POST['category_id'] ?? $assignment['category_id']);
        $topic = trim($_POST['topic'] ?? $assignment['topic']);
        $description = trim($_POST['description'] ?? $assignment['description']);
        $submissionDeadline = trim($_POST['submission_deadline'] ?? $assignment['submission_deadline']);
        $publishingSchedule = trim($_POST['publishing_schedule'] ?? $assignment['publishing_schedule']);
        $status = trim($_POST['status'] ?? $assignment['status']);

        $submissionDeadlineFormatted = date('Y-m-d H:i:s', strtotime($submissionDeadline));
        $publishingScheduleFormatted = date('Y-m-d H:i:s', strtotime($publishingSchedule));

        Assignment::update($id, [
            'assigned_to_user_id' => $assignedTo,
            'category_id' => $categoryId,
            'topic' => $topic,
            'description' => $description,
            'submission_deadline' => $submissionDeadlineFormatted,
            'publishing_schedule' => $publishingScheduleFormatted,
            'status' => in_array($status, ['pending', 'turned_in', 'approved', 'expired']) ? $status : 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        redirect('dashboard/editorial?notice=assignment_updated#assignments');
    }

    /**
     * Editor Assignment Deletion
     */
    public function delete($id)
    {
        $this->checkEditorAccess();

        $assignment = Assignment::find($id);
        if ($assignment) {
            Assignment::delete($id);
        }

        redirect('dashboard/editorial?notice=assignment_deleted#assignments');
    }

    /**
     * Notification API: Get unread count and latest items
     */
    public function apiNotifications()
    {
        header('Content-Type: application/json');
        if (!auth()->check()) {
            echo json_encode(['unread_count' => 0, 'notifications' => []]);
            exit;
        }

        Assignment::checkAndGenerateReminders();

        $userId = auth()->id();
        $notifications = Notification::getUserNotifications($userId, 15);
        $unreadCount = Notification::getUnreadCount($userId);

        echo json_encode([
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
        exit;
    }

    /**
     * Notification API: Mark single as read
     */
    public function apiMarkRead($id)
    {
        header('Content-Type: application/json');
        if (!auth()->check()) {
            echo json_encode(['success' => false]);
            exit;
        }

        Notification::markAsRead($id, auth()->id());
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Notification API: Mark all as read
     */
    public function apiMarkAllRead()
    {
        header('Content-Type: application/json');
        if (!auth()->check()) {
            echo json_encode(['success' => false]);
            exit;
        }

        Notification::markAllAsRead(auth()->id());
        echo json_encode(['success' => true]);
        exit;
    }
}
