<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\SystemSetting;
use App\Models\Poll;
use App\Models\Announcement;

require_once __DIR__ . '/../../Models/Article.php';
require_once __DIR__ . '/../../Models/Category.php';
require_once __DIR__ . '/../../Models/SystemSetting.php';
require_once __DIR__ . '/../../Models/Poll.php';
require_once __DIR__ . '/../../Models/Announcement.php';

class HomeController
{
    public function index()
    {
        $categorySlug = $_GET['category'] ?? null;
        $search = trim($_GET['q'] ?? '');

        $activeCategory = null;
        $categoryId = null;

        if ($categorySlug) {
            $activeCategory = Category::findBy('slug', $categorySlug);
            if ($activeCategory) {
                $categoryId = $activeCategory['id'];
            }
        }

        // Fetch published articles
        $allPublished = Article::getPublished(24, $categoryId, $search ?: null);
        $allArticlesForGrid = $allPublished;
        $categories = Category::allWithArticleCount();

        $headline = null;
        $subHeadlines = [];
        $feedPosts = [];

        if (empty($categoryId) && empty($search)) {
            // 1. Check for editor-promoted headline via SystemSetting or is_headline column
            $headlineId = (int)SystemSetting::get('headline_article_id', 0);
            
            if ($headlineId > 0) {
                foreach ($allPublished as $idx => $art) {
                    if ((int)$art['id'] === $headlineId) {
                        $headline = $art;
                        unset($allPublished[$idx]);
                        break;
                    }
                }
                // If the promoted article is not in the recent batch, fetch directly
                if (!$headline) {
                    $promoted = Article::findWithDetails($headlineId);
                    if ($promoted && $promoted['status'] === 'published') {
                        $headline = $promoted;
                        foreach ($allPublished as $idx => $art) {
                            if ((int)$art['id'] === $headlineId) {
                                unset($allPublished[$idx]);
                                break;
                            }
                        }
                    }
                }
            }

            // Fallback: check is_headline column
            if (!$headline) {
                foreach ($allPublished as $idx => $art) {
                    if (!empty($art['is_headline'])) {
                        $headline = $art;
                        unset($allPublished[$idx]);
                        break;
                    }
                }
            }


            // Fallback: newest published article
            if (!$headline && !empty($allPublished)) {
                $headline = array_shift($allPublished);
            }

            // 2. Curated school stories (4 promoted stories set by editor on /dashboard/editorial, excluding active headline)
            $curatedSetting = SystemSetting::get('curated_article_ids', '[]');
            $curatedIds = json_decode($curatedSetting, true);
            if (!is_array($curatedIds) || empty($curatedIds)) {
                $curatedIds = [];
            }
            $curatedIds = array_values(array_map('intval', $curatedIds));

            // Exclude current headline from curated list if duplicate
            if ($headline) {
                $curatedIds = array_values(array_filter($curatedIds, function($cId) use ($headline) {
                    return (int)$cId !== (int)$headline['id'];
                }));
            }

            // Map available articles from $allPublished
            $curatedMap = [];
            foreach ($allPublished as $idx => $art) {
                if ($headline && (int)$art['id'] === (int)$headline['id']) {
                    unset($allPublished[$idx]);
                    continue;
                }
                if (in_array((int)$art['id'], $curatedIds, true)) {
                    $curatedMap[(int)$art['id']] = $art;
                    unset($allPublished[$idx]);
                }
            }

            // If any promoted curated stories were outside the initial published batch, fetch directly
            foreach ($curatedIds as $cId) {
                if (!isset($curatedMap[$cId])) {
                    $promoted = Article::findWithDetails($cId);
                    if ($promoted && $promoted['status'] === 'published') {
                        if (!$headline || (int)$promoted['id'] !== (int)$headline['id']) {
                            $curatedMap[$cId] = $promoted;
                        }
                    }
                }
            }

            // Populate $subHeadlines in defined order (up to 4)
            foreach ($curatedIds as $cId) {
                if (isset($curatedMap[$cId]) && count($subHeadlines) < 4) {
                    $subHeadlines[] = $curatedMap[$cId];
                }
            }

            // Fallback 1: check is_curated column for any remaining published articles
            if (count($subHeadlines) < 4) {
                $selectedIds = array_column($subHeadlines, 'id');
                foreach ($allPublished as $idx => $art) {
                    if ($headline && (int)$art['id'] === (int)$headline['id']) {
                        unset($allPublished[$idx]);
                        continue;
                    }
                    if (!empty($art['is_curated']) && !in_array((int)$art['id'], $selectedIds, true)) {
                        $subHeadlines[] = $art;
                        $selectedIds[] = (int)$art['id'];
                        unset($allPublished[$idx]);
                        if (count($subHeadlines) >= 4) {
                            break;
                        }
                    }
                }
            }

            // Fallback 2: fill up to 4 if any slots still empty from newest published
            while (count($subHeadlines) < 4 && !empty($allPublished)) {
                $candidate = array_shift($allPublished);
                if ($headline && (int)$candidate['id'] === (int)$headline['id']) {
                    continue;
                }
                $subHeadlines[] = $candidate;
            }

            $feedPosts = array_values($allPublished);
        } else {
            $feedPosts = $allPublished;
        }

        $platformName = SystemSetting::get('platform_name', 'Brighteen');
        $tagline = SystemSetting::get('tagline', 'The Youth Pulse of Culture, Tech & Creativity');
        $announcements = Announcement::getActive(5);
        $latestUpdatedStories = Article::getLatestUpdated(5);

        // Fetch recently promoted contributors for the live ticker
        $latestPromotedContributors = db()->query("
            SELECT ca.author_name, ca.full_name, ca.institution, u.name as user_name, ca.reviewed_at
            FROM contributor_applications ca
            JOIN users u ON ca.user_id = u.id
            WHERE ca.status = 'approved'
            ORDER BY ca.reviewed_at DESC, ca.id DESC
            LIMIT 2
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Attach interactive poll/quiz data
        if ($headline) {
            Poll::attachToArticle($headline);
        }
        Poll::attachToArticles($subHeadlines);
        Poll::attachToArticles($feedPosts);

        return view('home', [
            'headline' => $headline,
            'subHeadlines' => $subHeadlines,
            'feedPosts' => $feedPosts,
            'articles' => $allArticlesForGrid, // for explore grid / general fallback
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'search' => $search,
            'platformName' => $platformName,
            'tagline' => $tagline,
            'announcements' => $announcements,
            'latestPromotedContributors' => $latestPromotedContributors,
            'latestUpdatedStories' => $latestUpdatedStories,
        ]);
    }
}
