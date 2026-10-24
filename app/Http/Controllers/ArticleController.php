<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\SavedPost;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/Comment.php';
require_once __DIR__ . '/../../Models/Like.php';
require_once __DIR__ . '/../../Models/SavedPost.php';

class ArticleController
{
    public function show($slug)
    {
        $article = Article::findWithDetails($slug);

        if (!$article) {
            http_response_code(404);
            return view('errors.404', ['message' => 'Article not found.']);
        }

        // Only allow published articles to be viewed publicly unless user is author or editor/management
        if ($article['status'] !== 'published') {
            $canViewDraft = auth()->check() && (
                auth()->id() == $article['user_id'] ||
                auth()->isEditor() ||
                auth()->isManagement()
            );

            if (!$canViewDraft) {
                http_response_code(403);
                return view('errors.403', ['message' => 'This article is currently under editorial review.']);
            }
        }

        // Increment views count (only for published articles)
        if ($article['status'] === 'published') {
            Article::incrementViews($article['id']);
            $article['views_count']++;
        }

        // Fetch comments and related articles
        $comments = Comment::forArticle($article['id']);
        $related = Article::getPublished(4, $article['category_id']);

        // Filter out current article from related
        $related = array_filter($related, function($a) use ($article) {
            return $a['id'] != $article['id'];
        });

        return view('articles.show', [
            'article' => $article,
            'comments' => $comments,
            'related' => array_slice($related, 0, 3)
        ]);
    }

    public function like($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        $userId = auth()->id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $result = Like::toggle($id, $userId, $ip);
        return response()->json($result);
    }

    public function save($id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required', 'auth_required' => true], 401);
        }

        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        $result = SavedPost::toggle($id, auth()->id());
        return response()->json($result);
    }

    public function comment($id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required', 'auth_required' => true], 401);
        }

        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            return response()->json(['error' => 'Comment cannot be blank'], 422);
        }

        $commentId = Comment::create([
            'article_id' => $id,
            'user_id' => auth()->id(),
            'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
        ]);

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
                'user_name' => auth()->user()['name'],
                'user_avatar' => auth()->user()['avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80',
                'role_name' => auth()->role(),
                'time_ago' => 'Just now'
            ]
        ]);
    }

    public function bookmarks()
    {
        if (!auth()->check()) {
            redirect('login?redirect=bookmarks');
        }

        $savedArticles = SavedPost::getSavedArticlesForUser(auth()->id());

        return view('bookmarks', [
            'articles' => $savedArticles
        ]);
    }
}
