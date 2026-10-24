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
            'source' => $result['source'] ?? 'gemini',
            'result' => $result['content'] ?? ''
        ]);
    }
}
