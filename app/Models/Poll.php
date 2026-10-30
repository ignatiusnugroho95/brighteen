<?php

namespace App\Models;

use PDO;

class Poll extends Model
{
    protected static $table = 'polls';

    /**
     * Get the poll for an article with its options and computed percentages
     */
    public static function forArticle($articleId, $ip = null, $sessionId = null, $userId = null)
    {
        if (!$articleId) return null;

        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $sessionId = $sessionId ?: (session_id() ?: 'guest');
        $userId = $userId ?: (auth()->id() ?: null);

        $stmt = db()->prepare("SELECT * FROM `polls` WHERE article_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$articleId]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$poll) return null;

        // Fetch options
        $optStmt = db()->prepare("SELECT * FROM `poll_options` WHERE poll_id = ? ORDER BY order_num ASC, id ASC");
        $optStmt->execute([$poll['id']]);
        $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalVotes = array_sum(array_column($options, 'votes_count'));
        $poll['total_votes'] = (int)$totalVotes;

        // Compute percentage for each option
        foreach ($options as &$opt) {
            $votes = (int)$opt['votes_count'];
            $opt['votes_count'] = $votes;
            $opt['percentage'] = $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0;
            $opt['is_correct'] = (bool)$opt['is_correct'];
        }
        unset($opt);

        $poll['options'] = $options;

        // Check if current user or guest IP/session has already voted
        $poll['has_voted'] = false;
        $poll['user_voted_option_id'] = null;

        $checkParams = [$poll['id']];
        $whereClause = "poll_id = ? AND (";
        $conditions = ["ip_address = ?"];
        $checkParams[] = $ip;

        if ($sessionId) {
            $conditions[] = "session_id = ?";
            $checkParams[] = $sessionId;
        }
        if ($userId) {
            $conditions[] = "user_id = ?";
            $checkParams[] = $userId;
        }
        $whereClause .= implode(' OR ', $conditions) . ")";

        $voteStmt = db()->prepare("SELECT option_id FROM `poll_votes` WHERE {$whereClause} LIMIT 1");
        $voteStmt->execute($checkParams);
        $existingVote = $voteStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingVote) {
            $poll['has_voted'] = true;
            $poll['user_voted_option_id'] = (int)$existingVote['option_id'];
        }

        return $poll;
    }

    /**
     * Attach poll data to a single article array
     */
    public static function attachToArticle(&$article, $ip = null, $sessionId = null, $userId = null)
    {
        if (empty($article) || empty($article['id'])) return $article;
        $article['poll'] = self::forArticle($article['id'], $ip, $sessionId, $userId);
        return $article;
    }

    /**
     * Batch attach poll data to an array of articles
     */
    public static function attachToArticles(&$articles, $ip = null, $sessionId = null, $userId = null)
    {
        if (empty($articles)) return $articles;
        foreach ($articles as &$art) {
            if (isset($art['id'])) {
                $art['poll'] = self::forArticle($art['id'], $ip, $sessionId, $userId);
            }
        }
        unset($art);
        return $articles;
    }

    /**
     * Save or update poll for an article
     */
    public static function saveForArticle($articleId, array $data)
    {
        $question = trim($data['question'] ?? '');
        $options = $data['options'] ?? [];
        $pollType = in_array($data['poll_type'] ?? '', ['poll', 'quiz']) ? $data['poll_type'] : 'poll';
        $correctIndex = isset($data['correct_index']) ? (int)$data['correct_index'] : (isset($data['correct_option_index']) ? (int)$data['correct_option_index'] : -1);

        if (empty($question) || empty($options) || count($options) < 2) {
            // If user cleared the poll, delete existing poll
            if (empty($question)) {
                $del = db()->prepare("DELETE FROM `polls` WHERE article_id = ?");
                $del->execute([$articleId]);
            }
            return null;
        }

        // Check if poll already exists for this article
        $stmt = db()->prepare("SELECT id FROM `polls` WHERE article_id = ? LIMIT 1");
        $stmt->execute([$articleId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pollId = $existing['id'];
            $upd = db()->prepare("UPDATE `polls` SET question = ?, poll_type = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$question, $pollType, $pollId]);

            // Keep existing options votes if text matches, or refresh
            $oldOptions = db()->query("SELECT id, option_text, votes_count FROM `poll_options` WHERE poll_id = {$pollId}")->fetchAll(PDO::FETCH_ASSOC);
            $oldMap = [];
            foreach ($oldOptions as $old) {
                $oldMap[strtolower(trim($old['option_text']))] = $old['votes_count'];
            }

            db()->exec("DELETE FROM `poll_options` WHERE poll_id = {$pollId}");
        } else {
            $ins = db()->prepare("INSERT INTO `polls` (article_id, question, poll_type, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $ins->execute([$articleId, $question, $pollType]);
            $pollId = db()->lastInsertId();
            $oldMap = [];
        }

        // Insert options
        $optIns = db()->prepare("INSERT INTO `poll_options` (poll_id, option_text, is_correct, order_num, votes_count) VALUES (?, ?, ?, ?, ?)");
        $order = 0;
        foreach ($options as $idx => $optText) {
            $text = trim($optText);
            if ($text === '') continue;

            $isCorrect = ($pollType === 'quiz' && $idx === $correctIndex) ? 1 : 0;
            $votes = $oldMap[strtolower($text)] ?? 0;

            $optIns->execute([$pollId, $text, $isCorrect, $order, $votes]);
            $order++;
        }

        return $pollId;
    }

    /**
     * Record a vote (no login required for readers/guests)
     */
    public static function vote($pollId, $optionId, $ip = null, $sessionId = null, $userId = null)
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $sessionId = $sessionId ?: (session_id() ?: 'guest');
        $userId = $userId ?: (auth()->id() ?: null);

        // Verify poll and option exist
        $pollStmt = db()->prepare("SELECT * FROM `polls` WHERE id = ?");
        $pollStmt->execute([$pollId]);
        $poll = $pollStmt->fetch(PDO::FETCH_ASSOC);

        if (!$poll) {
            return ['success' => false, 'error' => 'Poll not found.'];
        }

        $optStmt = db()->prepare("SELECT * FROM `poll_options` WHERE id = ? AND poll_id = ?");
        $optStmt->execute([$optionId, $pollId]);
        $option = $optStmt->fetch(PDO::FETCH_ASSOC);

        if (!$option) {
            return ['success' => false, 'error' => 'Invalid option.'];
        }

        // Check if already voted
        $checkParams = [$pollId];
        $whereClause = "poll_id = ? AND (";
        $conditions = ["ip_address = ?"];
        $checkParams[] = $ip;

        if ($sessionId) {
            $conditions[] = "session_id = ?";
            $checkParams[] = $sessionId;
        }
        if ($userId) {
            $conditions[] = "user_id = ?";
            $checkParams[] = $userId;
        }
        $whereClause .= implode(' OR ', $conditions) . ")";

        $voteStmt = db()->prepare("SELECT option_id FROM `poll_votes` WHERE {$whereClause} LIMIT 1");
        $voteStmt->execute($checkParams);
        $existing = $voteStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Already voted, return current results
            $refreshed = self::forArticle($poll['article_id'], $ip, $sessionId, $userId);
            return [
                'success' => true,
                'already_voted' => true,
                'poll' => $refreshed,
                'is_correct' => (bool)$option['is_correct'],
                'message' => 'You have already voted on this poll.'
            ];
        }

        // Record vote
        $voteIns = db()->prepare("INSERT INTO `poll_votes` (poll_id, option_id, user_id, ip_address, session_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $voteIns->execute([$pollId, $optionId, $userId, $ip, $sessionId]);

        // Increment votes_count on option
        $optUpd = db()->prepare("UPDATE `poll_options` SET votes_count = votes_count + 1 WHERE id = ?");
        $optUpd->execute([$optionId]);

        // Fetch refreshed state
        $refreshed = self::forArticle($poll['article_id'], $ip, $sessionId, $userId);

        return [
            'success' => true,
            'poll' => $refreshed,
            'is_correct' => (bool)$option['is_correct'],
            'user_voted_option_id' => (int)$optionId,
            'message' => 'Vote recorded successfully!'
        ];
    }
}
