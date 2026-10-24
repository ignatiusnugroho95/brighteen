<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;

require_once __DIR__ . '/../../Services/GeminiService.php';

class AiController
{
    public function generate()
    {
        // Protected for content creators (Journalist, Contributor, Editor, Management)
        if (!auth()->check() || !auth()->canCreateArticle()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Only Journalists, Contributors, and Editors can access the AI Writer Assistant.'
            ], 403);
        }

        // Support both application/json and form-data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? 'draft';
        $prompt = trim($input['prompt'] ?? '');
        $text = trim($input['text'] ?? '');
        $topic = trim($input['topic'] ?? $prompt);
        $notes = trim($input['notes'] ?? '');
        $niche = trim($input['niche'] ?? 'Tech & Youth Culture');

        $gemini = new GeminiService();

        switch ($action) {
            case 'draft':
                if (empty($topic)) {
                    return response()->json(['success' => false, 'error' => 'A topic or prompt is required for Auto-Draft generation.'], 422);
                }
                $result = $gemini->generateAutoDraft($topic, $notes);
                break;

            case 'title':
            case 'seo':
                $target = $topic ?: $text;
                if (empty($target)) {
                    return response()->json(['success' => false, 'error' => 'Draft text or topic is required to optimize titles.'], 422);
                }
                $result = $gemini->optimizeTitlesAndSeo($target);
                break;

            case 'rephrase':
                if (empty($text)) {
                    return response()->json(['success' => false, 'error' => 'Selected text is required to rephrase.'], 422);
                }
                $result = $gemini->rephraseAndSummarize($text, 'rephrase');
                break;

            case 'summarize':
                if (empty($text)) {
                    return response()->json(['success' => false, 'error' => 'Selected text is required to summarize.'], 422);
                }
                $result = $gemini->rephraseAndSummarize($text, 'summarize');
                break;

            case 'grammar':
            case 'tone':
                if (empty($text)) {
                    return response()->json(['success' => false, 'error' => 'Draft text is required to check grammar and tone.'], 422);
                }
                $result = $gemini->checkGrammarAndTone($text);
                break;

            case 'ideas':
                $result = $gemini->generateContentIdeas($niche);
                break;

            default:
                return response()->json(['success' => false, 'error' => "Unknown AI action: [{$action}]."], 400);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'source' => $result['source'] ?? 'Brighteen AI Engine',
            'result' => $result['content'] ?? ''
        ]);
    }

    /**
     * Get current AI status and engine info
     */
    public function status()
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $gemini = new GeminiService();
        $hasKey = $gemini->hasLiveKey();
        $model = $gemini->getModel();

        return response()->json([
            'success' => true,
            'has_key' => $hasKey,
            'model' => $model,
            'engine' => $hasKey ? "Google Gemini ({$model})" : "Brighteen Contextual AI (Local)"
        ]);
    }

    /**
     * Save / Update Google Gemini API Key
     */
    public function saveKey()
    {
        if (!auth()->check() || (!auth()->isEditor() && !auth()->isManagement() && !auth()->canCreateArticle())) {
            return response()->json(['success' => false, 'error' => 'Unauthorized to update AI credentials.'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $key = trim($input['api_key'] ?? '');
        $model = trim($input['model'] ?? 'gemini-3.5-flash');

        $gemini = new GeminiService();
        $gemini->saveApiKey($key, $model);

        return response()->json([
            'success' => true,
            'message' => empty($key) ? 'Gemini API Key removed. Reverted to Contextual AI.' : 'Google Gemini API Key successfully saved and activated!',
            'has_key' => !empty($key),
            'model' => $model,
            'engine' => !empty($key) ? "Google Gemini ({$model})" : "Brighteen Contextual AI (Local)"
        ]);
    }
}

