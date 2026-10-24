<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Assignment;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/Assignment.php';

class EditorController
{
    private function checkCreatorAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=articles/create');
        }

        if (!auth()->canCreateArticle()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Only contributors, journalists, and editors can access the writer workbench.']));
        }
    }

    public function create()
    {
        $this->checkCreatorAccess();
        $categories = Category::all('name ASC');

        $assignmentId = (int)($_GET['assignment_id'] ?? 0);
        $userAssignments = auth()->check() ? Assignment::getActivePendingForJournalist(auth()->id()) : [];
        $selectedAssignment = null;
        $prefillArticle = null;

        if ($assignmentId > 0) {
            $selectedAssignment = Assignment::findDetailed($assignmentId);
            if ($selectedAssignment) {
                $prefillArticle = [
                    'title' => $selectedAssignment['topic'],
                    'category_id' => $selectedAssignment['category_id'],
                    'excerpt' => $selectedAssignment['description']
                ];
            }
        }

        return view('articles.editor', [
            'article' => $prefillArticle,
            'categories' => $categories,
            'isEdit' => false,
            'userAssignments' => $userAssignments,
            'selectedAssignmentId' => $assignmentId,
            'selectedAssignment' => $selectedAssignment
        ]);
    }

    public function edit($id)
    {
        $this->checkCreatorAccess();
        $article = Article::find($id);

        if (!$article) {
            http_response_code(404);
            return view('errors.404', ['message' => 'Article not found.']);
        }

        // RBAC Enforcement: Contributors & Journalists can only edit their own articles
        if (!auth()->isEditor() && !auth()->isManagement()) {
            if ($article['user_id'] != auth()->id()) {
                http_response_code(403);
                return view('errors.403', ['message' => 'You do not have permission to edit this article.']);
            }
        }

        $categories = Category::all('name ASC');
        $userAssignments = auth()->check() ? Assignment::getActivePendingForJournalist(auth()->id()) : [];

        return view('articles.editor', [
            'article' => $article,
            'categories' => $categories,
            'isEdit' => true,
            'userAssignments' => $userAssignments,
            'selectedAssignmentId' => 0,
            'selectedAssignment' => null
        ]);
    }

    public function store()
    {
        $this->checkCreatorAccess();

        $title = trim($_POST['title'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $content = $_POST['content'] ?? '';
        $excerpt = trim($_POST['excerpt'] ?? '');
        $featuredImage = trim($_POST['featured_image'] ?? '');
        $action = $_POST['action'] ?? 'save_draft';

        if (empty($title)) {
            $categories = Category::all('name ASC');
            $userAssignments = auth()->check() ? Assignment::getActivePendingForJournalist(auth()->id()) : [];
            return view('articles.editor', [
                'article' => $_POST,
                'categories' => $categories,
                'isEdit' => false,
                'userAssignments' => $userAssignments,
                'selectedAssignmentId' => $assignmentId,
                'selectedAssignment' => $assignmentId ? Assignment::findDetailed($assignmentId) : null,
                'error' => 'Title is required.'
            ]);
        }

        // Create URL slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = $baseSlug ?: 'story-' . time();
        $existing = Article::findBy('slug', $slug);
        if ($existing) {
            $slug .= '-' . time();
        }

        // Determine status according to RBAC Matrix
        $status = 'draft';
        $publishedAt = null;

        if ($action === 'publish') {
            if (auth()->canPublishDirectly()) {
                $status = 'published';
                $publishedAt = date('Y-m-d H:i:s');
            } else {
                // Journalists & Contributors CANNOT self-publish directly; strictly enforced
                $status = 'pending_review';
            }
        } elseif ($action === 'submit_review') {
            $status = 'pending_review';
        }

        $id = Article::create([
            'user_id' => auth()->id(),
            'category_id' => $categoryId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $excerpt,
            'featured_image' => $featuredImage ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80',
            'status' => $status,
            'views_count' => 0,
            'published_at' => $publishedAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Link and Turn In Assignment if assigned
        if ($assignmentId > 0) {
            Assignment::markTurnedIn($assignmentId, $id);
        }

        if ($status === 'published') {
            redirect('articles/' . $slug);
        } elseif ($assignmentId > 0) {
            redirect('dashboard/assignments?notice=assignment_turned_in');
        } elseif (auth()->isEditor() || auth()->isManagement()) {
            redirect('dashboard/editorial?notice=article_saved');
        } else {
            redirect('?notice=submitted_for_review');
        }
    }

    public function update($id)
    {
        $this->checkCreatorAccess();
        $article = Article::find($id);

        if (!$article) {
            http_response_code(404);
            return view('errors.404', ['message' => 'Article not found.']);
        }

        // Check ownership
        if (!auth()->isEditor() && !auth()->isManagement()) {
            if ($article['user_id'] != auth()->id()) {
                http_response_code(403);
                return view('errors.403', ['message' => 'Unauthorized action.']);
            }
        }

        $title = trim($_POST['title'] ?? $article['title']);
        $categoryId = (int)($_POST['category_id'] ?? $article['category_id']);
        $content = $_POST['content'] ?? $article['content'];
        $excerpt = trim($_POST['excerpt'] ?? $article['excerpt']);
        $featuredImage = trim($_POST['featured_image'] ?? $article['featured_image']);
        if (empty($featuredImage)) {
            $featuredImage = 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80';
        }
        $action = $_POST['action'] ?? 'save_draft';

        $status = $article['status'];
        $publishedAt = $article['published_at'];

        if ($action === 'publish') {
            if (auth()->canPublishDirectly()) {
                $status = 'published';
                if (!$publishedAt) {
                    $publishedAt = date('Y-m-d H:i:s');
                }
            } else {
                $status = 'pending_review';
            }
        } elseif ($action === 'submit_review') {
            $status = 'pending_review';
        } elseif ($action === 'save_draft') {
            if ($status !== 'published') {
                $status = 'draft';
            }
        }

        Article::update($id, [
            'category_id' => $categoryId,
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'featured_image' => $featuredImage,
            'status' => $status,
            'published_at' => $publishedAt,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($status === 'published') {
            redirect('articles/' . $article['slug']);
        } elseif (auth()->isEditor() || auth()->isManagement()) {
            redirect('dashboard/editorial?notice=article_updated');
        } else {
            redirect('?notice=draft_updated');
        }
    }

    public function uploadImage()
    {
        $this->checkCreatorAccess();

        if (empty($_FILES['upload']['tmp_name'])) {
            return response()->json(['error' => ['message' => 'No image uploaded']], 400);
        }

        $file = $_FILES['upload'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed)) {
            return response()->json(['error' => ['message' => 'Invalid image type. JPEG, PNG, WEBP, and GIF only.']], 422);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destination = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = url('uploads/' . $filename);
            return response()->json([
                'url' => $url
            ]);
        }

        return response()->json(['error' => ['message' => 'Upload failed']], 500);
    }
}
