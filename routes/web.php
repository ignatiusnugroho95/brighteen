<?php

use App\Http\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\EditorialDashboardController;
use App\Http\Controllers\ManagementDashboardController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AboutController;

require_once __DIR__ . '/../app/Http/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Http/Controllers/ArticleController.php';
require_once __DIR__ . '/../app/Http/Controllers/EditorController.php';
require_once __DIR__ . '/../app/Http/Controllers/EditorialDashboardController.php';
require_once __DIR__ . '/../app/Http/Controllers/ManagementDashboardController.php';
require_once __DIR__ . '/../app/Http/Controllers/AssignmentController.php';
require_once __DIR__ . '/../app/Http/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Http/Controllers/AiController.php';
require_once __DIR__ . '/../app/Http/Controllers/AboutController.php';

// Public Homepage & Feeds
Router::get('', [HomeController::class, 'index']);
Router::get('home', [HomeController::class, 'index']);
Router::get('articles', [HomeController::class, 'index']);

// About Us Page (Public & Editor/Management Edit)
Router::get('about', [AboutController::class, 'show']);
Router::get('about/edit', [AboutController::class, 'edit']);
Router::post('about/update', [AboutController::class, 'update']);
Router::post('about/contributor-apply', [AboutController::class, 'applyContributor']);

// Bookmarks / Saved Posts
Router::get('bookmarks', [ArticleController::class, 'bookmarks']);

// Journalist Assignment Center
Router::get('dashboard/assignments', [AssignmentController::class, 'index']);
Router::get('assignments', [AssignmentController::class, 'index']);

// Articles Workbench & Creation (Must precede articles/{slug})
Router::get('articles/create', [EditorController::class, 'create']);
Router::post('articles', [EditorController::class, 'store']);
Router::get('articles/{id}/edit', [EditorController::class, 'edit']);
Router::post('articles/{id}/update', [EditorController::class, 'update']);
Router::post('articles/{id}/delete', [EditorController::class, 'destroy']);

// Article Detail View
Router::get('articles/{slug}', [ArticleController::class, 'show']);

// Editorial Review Dashboard (Editor & Management)
Router::get('dashboard/editorial', [EditorialDashboardController::class, 'index']);
Router::post('dashboard/editorial/articles/{id}/approve', [EditorialDashboardController::class, 'approve']);
Router::post('dashboard/editorial/articles/{id}/reject', [EditorialDashboardController::class, 'reject']);
Router::post('dashboard/editorial/articles/{id}/headline', [EditorialDashboardController::class, 'promoteHeadline']);
Router::post('dashboard/editorial/articles/{id}/promote-headline', [EditorialDashboardController::class, 'promoteHeadline']);
Router::post('dashboard/editorial/articles/{id}/curated', [EditorialDashboardController::class, 'promoteCurated']);
Router::post('dashboard/editorial/articles/{id}/promote-curated', [EditorialDashboardController::class, 'promoteCurated']);
Router::post('dashboard/editorial/articles/{id}/demote-curated', [EditorialDashboardController::class, 'demoteCurated']);
Router::post('dashboard/editorial/categories', [EditorialDashboardController::class, 'storeCategory']);
Router::post('dashboard/editorial/categories/update', [EditorialDashboardController::class, 'updateCategory']);
Router::post('dashboard/editorial/categories/{id}/update', [EditorialDashboardController::class, 'updateCategory']);
Router::post('dashboard/editorial/categories/delete', [EditorialDashboardController::class, 'deleteCategory']);
Router::post('dashboard/editorial/categories/{id}/delete', [EditorialDashboardController::class, 'deleteCategory']);
Router::post('dashboard/editorial/announcement', [EditorialDashboardController::class, 'updateAnnouncement']);
Router::post('dashboard/announcement', [EditorialDashboardController::class, 'updateAnnouncement']);

// Editorial Assignment Management (CRUD for Editor/Management)
Router::post('dashboard/editorial/assignments', [AssignmentController::class, 'store']);
Router::post('dashboard/editorial/assignments/{id}/update', [AssignmentController::class, 'update']);
Router::post('dashboard/editorial/assignments/{id}/delete', [AssignmentController::class, 'delete']);

// Platform Management Dashboard (Management Only)
Router::get('dashboard/management', [ManagementDashboardController::class, 'index']);
Router::get('management', [ManagementDashboardController::class, 'index']);
Router::post('dashboard/management/users/{id}/role', [ManagementDashboardController::class, 'updateUserRole']);
Router::post('dashboard/management/settings', [ManagementDashboardController::class, 'updateSettings']);
Router::post('dashboard/management/articles/{id}/delete', [ManagementDashboardController::class, 'deleteArticle']);
Router::post('dashboard/management/applications/{id}/approve', [ManagementDashboardController::class, 'approveApplication']);
Router::post('dashboard/management/applications/{id}/reject', [ManagementDashboardController::class, 'rejectApplication']);

// Authentication & Demo Role Switcher
Router::get('login', [AuthController::class, 'showLogin']);
Router::post('login', [AuthController::class, 'login']);
Router::get('register', [AuthController::class, 'showRegister']);
Router::post('register', [AuthController::class, 'register']);
Router::get('logout', [AuthController::class, 'logout']);
Router::get('switch-user/{role}', [AuthController::class, 'switchUser']);

// Interactive APIs
Router::post('api/articles/{id}/like', [ArticleController::class, 'like']);
Router::post('api/articles/{id}/save', [ArticleController::class, 'save']);
Router::post('api/articles/{id}/comment', [ArticleController::class, 'comment']);
Router::post('api/upload-image', [EditorController::class, 'uploadImage']);
Router::post('api/ai/generate', [AiController::class, 'generate']);
Router::get('api/ai/status', [AiController::class, 'status']);
Router::post('api/ai/save-key', [AiController::class, 'saveKey']);
Router::get('api/notifications', [AssignmentController::class, 'apiNotifications']);
Router::post('api/notifications/{id}/read', [AssignmentController::class, 'apiMarkRead']);
Router::post('api/notifications/mark-all-read', [AssignmentController::class, 'apiMarkAllRead']);
