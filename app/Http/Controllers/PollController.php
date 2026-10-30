<?php

namespace App\Http\Controllers;

use App\Models\Poll;

class PollController
{
    /**
     * Submit a vote on a poll (Open to everyone, no login required)
     */
    public function vote($id)
    {
        $pollId = (int)$id;

        // Support both application/json and form-data
        $input = json_decode(file_get_contents('php://input'), true);
        $optionId = (int)($input['option_id'] ?? ($_POST['option_id'] ?? 0));

        if (!$optionId) {
            return response()->json(['success' => false, 'error' => 'No option selected.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $sessionId = session_id() ?: (isset($_COOKIE['brighteen_guest_voter']) ? $_COOKIE['brighteen_guest_voter'] : null);

        if (!$sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            setcookie('brighteen_guest_voter', $sessionId, time() + (86400 * 365), '/');
        }

        $userId = auth()->id() ?: null;

        $result = Poll::vote($pollId, $optionId, $ip, $sessionId, $userId);

        return response()->json($result);
    }

    /**
     * Fetch live poll results
     */
    public function show($id)
    {
        $pollId = (int)$id;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $sessionId = session_id() ?: (isset($_COOKIE['brighteen_guest_voter']) ? $_COOKIE['brighteen_guest_voter'] : null);
        $userId = auth()->id() ?: null;

        $poll = Poll::find($pollId);
        if (!$poll) {
            return response()->json(['success' => false, 'error' => 'Poll not found.'], 404);
        }

        $pollData = Poll::forArticle($poll['article_id'], $ip, $sessionId, $userId);

        return response()->json([
            'success' => true,
            'poll' => $pollData
        ]);
    }
}
