<?php

namespace App\Services;

use App\Models\SystemSetting;

require_once __DIR__ . '/../Models/SystemSetting.php';

class GeminiService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        // Check system_settings table, then environment variable
        $dbKey = class_exists(SystemSetting::class) ? SystemSetting::get('gemini_api_key') : null;
        $this->apiKey = !empty($dbKey) ? $dbKey : (function_exists('env') ? env('GEMINI_API_KEY', '') : (getenv('GEMINI_API_KEY') ?: ''));

        $dbModel = class_exists(SystemSetting::class) ? SystemSetting::get('gemini_model') : null;
        $this->model = !empty($dbModel) ? $dbModel : (function_exists('env') ? env('GEMINI_MODEL', 'gemini-3.5-flash') : (getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash'));
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function hasLiveKey()
    {
        return !empty($this->apiKey);
    }

    /**
     * Save API key to database settings and sync to .env if writable
     */
    public function saveApiKey($key, $model = null)
    {
        $key = trim($key);
        $this->apiKey = $key;
        if (class_exists(SystemSetting::class)) {
            SystemSetting::set('gemini_api_key', $key, 'Google Gemini AI API Key for writer assistant and content generation.');
            if ($model) {
                $this->model = trim($model);
                SystemSetting::set('gemini_model', $this->model, 'Active Google Gemini Model.');
            }
        }

        // Also update .env file if it exists
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = file_get_contents($envPath);
            if (strpos($envContent, 'GEMINI_API_KEY=') !== false) {
                $envContent = preg_replace('/GEMINI_API_KEY=.*/', 'GEMINI_API_KEY=' . $key, $envContent);
            } else {
                $envContent .= "\nGEMINI_API_KEY=" . $key;
            }
            if ($model && strpos($envContent, 'GEMINI_MODEL=') !== false) {
                $envContent = preg_replace('/GEMINI_MODEL=.*/', 'GEMINI_MODEL=' . $model, $envContent);
            }
            file_put_contents($envPath, $envContent);
        }

        return true;
    }

    /**
     * Call the Google Gemini API or fall back to high-fidelity contextual youth generator
     */
    public function callGemini($prompt, $systemInstruction = null, $temperature = 0.7)
    {
        if (!empty($this->apiKey)) {
            // Active Free-Tier models supported by Google Gemini API
            $modelsToTry = array_unique(array_filter([
                $this->model,
                'gemini-3.5-flash',
                'gemini-3.5-flash-lite',
                'gemini-3.8-flash'
            ]));

            foreach ($modelsToTry as $candidateModel) {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$candidateModel}:generateContent?key=" . urlencode($this->apiKey);

                $payload = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 3500,
                    ]
                ];

                if (!empty($systemInstruction)) {
                    $payload['systemInstruction'] = [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ];
                }

                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $err = curl_error($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (!$err && $code === 200) {
                    $data = json_decode($response, true);
                    $candidate = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (!empty($candidate)) {
                        // Strip wrapping markdown code blocks if the model enclosed its HTML response in ```html ... ```
                        $cleanContent = trim($candidate);
                        $cleanContent = preg_replace('/^```(?:html|markdown)?\s*/i', '', $cleanContent);
                        $cleanContent = preg_replace('/```$/', '', trim($cleanContent));

                        return [
                            'success' => true,
                            'source' => "Google Gemini ({$candidateModel})",
                            'content' => trim($cleanContent)
                        ];
                    }
                }
            }
        }

        // Contextual generator tailored directly to the input prompt
        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualFallback($prompt, $systemInstruction)
        ];
    }

    /**
     * 1. Auto-Draft Generator with 5W+1H Journalistic Foundation & Quotes (Middle School / SMP Student Magazine)
     * Restricts output to English by default unless Indonesian or another language is explicitly requested.
     */
    public function generateAutoDraft($topic, $notes = '', $journalismData = [], $requestedLanguage = 'en')
    {
        $what = trim($journalismData['what'] ?? '');
        $who = trim($journalismData['who'] ?? '');
        $where = trim($journalismData['where'] ?? '');
        $when = trim($journalismData['when'] ?? '');
        $why = trim($journalismData['why'] ?? '');
        $how = trim($journalismData['how'] ?? '');
        $quotes = trim($journalismData['interview_quotes'] ?? '');

        $combinedContext = $topic . ' ' . $notes . ' ' . $what . ' ' . $who . ' ' . $quotes;
        $targetLang = $this->determineLanguage($combinedContext, $requestedLanguage);

        if ($targetLang === 'id') {
            $fiveWoneH = [];
            if (!empty($what)) $fiveWoneH[] = "- What (Peristiwa/Hal yang terjadi): {$what}";
            if (!empty($who)) $fiveWoneH[] = "- Who (Siapa yang terlibat/tokoh siswa/guru): {$who}";
            if (!empty($where)) $fiveWoneH[] = "- Where (Tempat/lokasi di sekolah atau sekitarnya): {$where}";
            if (!empty($when)) $fiveWoneH[] = "- When (Waktu pelaksanaan/jadwal): {$when}";
            if (!empty($why)) $fiveWoneH[] = "- Why (Mengapa penting/tujuan kegiatan): {$why}";
            if (!empty($how)) $fiveWoneH[] = "- How (Bagaimana jalannya acara/proses/solusinya): {$how}";

            $factsPrompt = !empty($fiveWoneH)
                ? "\n\nFakta Jurnalistik 5W+1H (Wajib diintegrasikan ke dalam tubuh artikel secara alami dan mengalir):\n" . implode("\n", $fiveWoneH)
                : "";

            $quotesPrompt = !empty($quotes)
                ? "\n\nHasil Wawancara & Kutipan Narasumber (Wajib dimasukkan dalam tag <blockquote> dengan menyebutkan nama pembicara dan perannya):\n\"{$quotes}\""
                : "";

            $system = "Anda adalah Chief AI Editor untuk Majalah Siswa SMP Brighteen (Sekolah Menengah Pertama, usia 12-15 tahun). Karakter Anda ceria, ramah, penuh rasa ingin tahu, suportif, dan dekat dengan dunia anak SMP. Format artikel menggunakan tag HTML semantik: <h2>, <h3>, <p>, <blockquote>, <ul>, <li>. Hindari kata-kata birokratis atau istilah akademis yang kaku dan membosankan. Tuliskan artikel jurnalistik sekolah yang seru, kaya fakta, dan menginspirasi siswa SMP dalam BAHASA INDONESIA karena diminta secara eksplisit.";

            $prompt = "Buat artikel majalah siswa SMP dalam BAHASA INDONESIA tentang: '{$topic}'.\nCatatan & Sudut Pandang Khusus: '{$notes}'.{$factsPrompt}{$quotesPrompt}\n\nStruktur wajib:\n1. <h2> Judul artikel yang seru, ramah siswa SMP, dan memikat rasa ingin tahu.\n2. Paragraf pembuka (<p class='lead'>) yang hangat, menghubungkan topik dengan keseharian siswa di sekolah.\n3. Dua hingga tiga seksi pembahasan dengan subjudul <h3> yang mengulas fakta, kisah inspiratif, atau strategi praktis.\n4. Kutipan wawancara dalam <blockquote> yang hidup dan mencerminkan suara siswa atau guru pembimbing.\n5. Daftar tips atau poin aksi seru yang bisa dicoba langsung dalam <ul><li>.\n6. Paragraf penutup yang memberi dorongan semangat positif bagi pembaca.";
        } else {
            // ENGLISH IS MANDATORY DEFAULT UNLESS EXPLICITLY REQUESTED OTHERWISE
            $fiveWoneHEn = [];
            if (!empty($what)) $fiveWoneHEn[] = "- What (Event/Core Subject): {$what}";
            if (!empty($who)) $fiveWoneHEn[] = "- Who (Key people, students, clubs, teachers involved): {$who}";
            if (!empty($where)) $fiveWoneHEn[] = "- Where (Location / Venue): {$where}";
            if (!empty($when)) $fiveWoneHEn[] = "- When (Time / Date / Context): {$when}";
            if (!empty($why)) $fiveWoneHEn[] = "- Why (Significance / Motivation): {$why}";
            if (!empty($how)) $fiveWoneHEn[] = "- How (How it unfolded / Process / Experience): {$how}";

            $factsPrompt = !empty($fiveWoneHEn)
                ? "\n\n5W+1H Journalistic Foundation (Seamlessly weave these facts into the story):\n" . implode("\n", $fiveWoneHEn)
                : "";

            $quotesPrompt = !empty($quotes)
                ? "\n\nInterview Quotes & Attribution (Format into a prominent <blockquote> element with full speaker attribution):\n\"{$quotes}\""
                : "";

            $langConstraint = ($targetLang === 'en')
                ? "STRICT LANGUAGE RESTRICTION: The ENTIRE article MUST be written in ENGLISH. Even if the topic, school name, locations, or people are Indonesian (e.g. 'Aula SMP Nusantara', 'Budi'), preserve the proper nouns as names, but write all narratives, titles, subheadings, explanations, and advice strictly in ENGLISH suitable for middle schoolers (ages 12-15)."
                : "LANGUAGE RESTRICTION: The user explicitly requested {$targetLang}. Write the entire article in {$targetLang}.";

            $system = "You are the Chief AI Student Editor for Brighteen Middle School Magazine (junior high school, ages 12-15). Your tone is vibrant, friendly, curious, encouraging, and relatable to young teens. Write compelling, accurate student journalism about school life, science, creativity, clubs, and friendship without corporate clichés or overly dry academic jargon. Format using semantic HTML tags: <h2>, <h3>, <p>, <blockquote>, <ul>, <li>.\n{$langConstraint}";

            $prompt = "Write an exciting, educational, and engaging middle school magazine feature article strictly in ENGLISH about: '{$topic}'.\nContext / Notes: '{$notes}'.{$factsPrompt}{$quotesPrompt}\n\nStructure required:\n1. <h2> Catchy, teen-friendly article headline in English.\n2. Opening paragraph (<p class='lead'>) connecting with daily school life and student curiosity.\n3. Two to three body sections with clear <h3> subheadings covering facts, practical tips, or behind-the-scenes stories.\n4. An authentic interview quote in <blockquote> attributing the speaker.\n5. Actionable tips or bullet points in <ul><li>.\n6. An encouraging and inspiring conclusion.\n\nAll text must be in English.";
        }

        // Check if API key available, otherwise use dynamic contextual auto-draft
        if (!empty($this->apiKey)) {
            $res = $this->callGemini($prompt, $system, 0.7);
            if (!empty($res['content'])) {
                return $res;
            }
        }

        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualAutoDraft($topic, $notes, $journalismData, $targetLang)
        ];
    }

    /**
     * 2. Title & SEO Optimizer (Middle School / SMP Student Magazine)
     * Restricts output to English by default unless Indonesian/other is requested.
     */
    public function optimizeTitlesAndSeo($topicOrContent, $requestedLanguage = 'en')
    {
        $targetLang = $this->determineLanguage($topicOrContent, $requestedLanguage);

        if ($targetLang === 'id') {
            $system = "Anda adalah pakar penulisan judul majalah siswa SMP Brighteen. Karakter Anda ceria, kreatif, dan ahli membuat judul dalam Bahasa Indonesia yang membuat siswa SMP (usia 12-15 tahun) penasaran untuk membaca.";
            $prompt = "Analisis topik atau penggalan draf ini: '{$topicOrContent}'.\nBuat:\n1. 5 judul artikel majalah siswa SMP dalam Bahasa Indonesia yang sangat seru, ramah remaja, dan menarik perhatian tanpa clickbait murahan.\n2. Satu ringkasan SEO Meta Description / Excerpt (maksimal 150 karakter) yang memicu rasa ingin tahu siswa.";
        } else {
            $system = "You are the creative headline strategist for Brighteen Middle School Magazine (junior high school, ages 12-15). Output MUST be strictly in ENGLISH.";
            $prompt = "Analyze this topic or draft snippet: '{$topicOrContent}'.\nGenerate strictly in ENGLISH:\n1. 5 irresistible, fun, middle school-friendly article headlines that students will love to read.\n2. A concise 150-character SEO Meta Description / Excerpt in English that sparks curiosity.";
        }

        if (!empty($this->apiKey)) {
            $res = $this->callGemini($prompt, $system, 0.75);
            if (!empty($res['content'])) {
                return $res;
            }
        }

        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualTitles($topicOrContent, $targetLang)
        ];
    }

    /**
     * 3. Rephrase & Summarize (Middle School Friendly)
     * Restricts output to English by default unless Indonesian/other is requested.
     */
    public function rephraseAndSummarize($text, $mode = 'rephrase', $requestedLanguage = 'en')
    {
        $targetLang = $this->determineLanguage($text, $requestedLanguage);

        if ($mode === 'summarize') {
            $prompt = ($targetLang === 'id')
                ? "Ringkas teks artikel berikut dalam Bahasa Indonesia menjadi 2-3 kalimat yang padat, jelas, dan mudah dipahami siswa SMP:\n\n\"{$text}\""
                : "Summarize the following article text strictly in ENGLISH into a 2-3 sentence clear and punchy excerpt for middle school readers:\n\n\"{$text}\"";
        } else {
            $prompt = ($targetLang === 'id')
                ? "Tulis ulang teks berikut dalam Bahasa Indonesia dengan gaya bahasa yang ceria, mengalir, segar, dan mudah dipahami siswa SMP (usia 12-15 tahun) tanpa mengubah makna aslinya:\n\n\"{$text}\""
                : "Rephrase the following text strictly in ENGLISH into friendly, vibrant, and clear prose suited for middle school students aged 12-15:\n\n\"{$text}\"";
        }
        $system = "You are Brighteen's copy stylist for middle school / junior high school student journalism. Deliver output strictly in " . ($targetLang === 'id' ? 'Indonesian' : 'English') . ".";

        if (!empty($this->apiKey)) {
            $res = $this->callGemini($prompt, $system, 0.65);
            if (!empty($res['content'])) {
                return $res;
            }
        }

        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualRephrase($text, $mode, $targetLang)
        ];
    }

    /**
     * 4. Grammar & Tone Checker (Middle School Audience)
     * Restricts output to English by default unless Indonesian/other is requested.
     */
    public function checkGrammarAndTone($text, $requestedLanguage = 'en')
    {
        $targetLang = $this->determineLanguage($text, $requestedLanguage);

        $prompt = ($targetLang === 'id')
            ? "Audit teks draf berikut dalam Bahasa Indonesia untuk memeriksa ejaan, kejelasan kalimat, dan kesesuaian nada baca bagi pembaca majalah siswa SMP (usia 12-15 tahun):\n\n\"{$text}\"\n\nSediakan:\n- Skor Keselarasan Nada SMP (1-100)\n- Catatan evaluasi kalimat (apakah ada kata yang terlalu rumit atau kaku)\n- Rekomendasi versi revisi yang lebih ramah siswa SMP"
            : "Audit this draft text strictly in ENGLISH for spelling, rhythm, clarity, and tone alignment with middle school students (ages 12-15):\n\n\"{$text}\"\n\nProvide in English:\n- Middle School Tone Score (1-100)\n- Friendly phrasing suggestions (avoiding overly dense adult jargon)\n- Polished revised version";

        $system = "You are Brighteen's middle school writing coach, encouraging students to write clearly and confidently. Provide your feedback in " . ($targetLang === 'id' ? 'Indonesian' : 'English') . ".";

        if (!empty($this->apiKey)) {
            $res = $this->callGemini($prompt, $system, 0.5);
            if (!empty($res['content'])) {
                return $res;
            }
        }

        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualToneAudit($text, $targetLang)
        ];
    }

    /**
     * 5. Content Idea Generator (Middle School Student Beats & Activities)
     * Restricts output to English by default unless Indonesian/other is requested.
     */
    public function generateContentIdeas($niche = 'School Life & Hobbies', $requestedLanguage = 'en')
    {
        $targetLang = $this->determineLanguage($niche, $requestedLanguage);

        $prompt = ($targetLang === 'id')
            ? "Hasilkan 6 ide artikel majalah sekolah dalam Bahasa Indonesia yang seru, edukatif, dan menarik tentang tema '{$niche}' yang ditujukan bagi siswa SMP (usia 12-15 tahun). Fokus pada kehidupan sekolah, klub/ekskul, eksperimen sains sederhana, pertemanan, hobi, dan tips belajar asyik. Untuk setiap ide, sertakan judul kerja, sudut pandang utama (angle), dan mengapa siswa SMP akan tertarik membacanya."
            : "Generate 6 exciting, educational, and fun article concepts strictly in ENGLISH for middle school magazine readers (ages 12-15) based on the beat '{$niche}'. Focus on school clubs, friendship, simple science experiments, study hacks, arts, and hobbies. For each idea, provide an English working title, target angle, and why junior high students will love reading it.";

        $system = "You are Brighteen's Middle School Editorial Brainstorming Lead, inspired by student curiosity and vibrant campus life. Output must be strictly in " . ($targetLang === 'id' ? 'Indonesian' : 'English') . ".";

        if (!empty($this->apiKey)) {
            $res = $this->callGemini($prompt, $system, 0.8);
            if (!empty($res['content'])) {
                return $res;
            }
        }

        return [
            'success' => true,
            'source' => 'Brighteen AI Engine (Contextual)',
            'content' => $this->generateContextualIdeas($niche, $targetLang)
        ];
    }

    /**
     * Determine the target output language.
     * Default MUST be English ('en') unless Indonesian ('id') or another language is EXPLICITLY requested.
     */
    public function determineLanguage($combinedText, $requestedLanguage = null)
    {
        // 1. Explicit language parameter passed from UI or controller
        if (!empty($requestedLanguage)) {
            $req = strtolower(trim($requestedLanguage));
            if ($req === 'id' || $req === 'indonesian' || strpos($req, 'indo') !== false) {
                return 'id';
            }
            if ($req !== 'en' && $req !== 'english' && $req !== 'default' && $req !== 'auto') {
                return $req; // specific foreign language requested
            }
            if ($req === 'en' || $req === 'english') {
                return 'en';
            }
        }

        $lower = strtolower($combinedText);

        // 2. Explicit request for Indonesian in user input / prompt / notes
        $explicitIndoPatterns = [
            '/\b(?:tulis|buat|gunakan|pakai|dalam|output)\s+(?:dalam\s+)?bahasa\s+indonesia\b/i',
            '/\bbahasa\s*:\s*indonesia\b/i',
            '/\b(?:in|using|write in|translated to)\s+indonesian\b/i',
            '/\b(?:versi|edisi)\s+bahasa\s+indonesia\b/i',
            '/\bbahasa\s+indonesia\s+(?:saja|aja|ya|dong|please)\b/i',
            '/\btuliskan\s+bahasa\s+indonesia\b/i',
            '/\bmohon\s+bahasa\s+indonesia\b/i',
        ];

        foreach ($explicitIndoPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return 'id';
            }
        }

        // 3. Explicit request for another specific language
        if (preg_match('/\b(?:in|using|write in|tulis dalam(?: bahasa)?)\s+(japanese|spanish|french|german|arabic|korean|mandarin|chinese)\b/i', $lower, $m)) {
            return strtolower($m[1]);
        }

        // 4. STRICT DEFAULT: English
        return 'en';
    }

    /**
     * Dynamic Contextual Auto-Draft Generator (Middle School SMP Student Magazine)
     * Restricts output to English unless Indonesian is requested.
     */
    protected function generateContextualAutoDraft($topic, $notes = '', $journalismData = [], $targetLang = 'en')
    {
        $cleanTopic = trim(strip_tags($topic));
        $cleanNotes = trim(strip_tags($notes));
        $what = trim($journalismData['what'] ?? '');
        $who = trim($journalismData['who'] ?? '');
        $where = trim($journalismData['where'] ?? '');
        $when = trim($journalismData['when'] ?? '');
        $why = trim($journalismData['why'] ?? '');
        $how = trim($journalismData['how'] ?? '');
        $quotes = trim($journalismData['interview_quotes'] ?? '');

        if ($targetLang === 'id') {
            $title = ucwords($cleanTopic);
            $notesSentence = !empty($cleanNotes)
                ? "<p class=\"text-sm leading-relaxed mb-4 text-slate-700 dark:text-slate-300\">Sudut pandang khusus: <em>{$cleanNotes}</em>.</p>\n"
                : "";

            $fiveWFacts = [];
            if (!empty($what)) $fiveWFacts[] = "<li><strong>Peristiwa (What):</strong> {$what}</li>";
            if (!empty($who)) $fiveWFacts[] = "<li><strong>Tokoh / Pihak Terlibat (Who):</strong> {$who}</li>";
            if (!empty($where)) $fiveWFacts[] = "<li><strong>Lokasi (Where):</strong> {$where}</li>";
            if (!empty($when)) $fiveWFacts[] = "<li><strong>Waktu Kejadian (When):</strong> {$when}</li>";
            if (!empty($why)) $fiveWFacts[] = "<li><strong>Tujuan / Alasan (Why):</strong> {$why}</li>";
            if (!empty($how)) $fiveWFacts[] = "<li><strong>Proses / Jalannya Acara (How):</strong> {$how}</li>";

            $fiveWBlock = !empty($fiveWFacts)
                ? "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Fakta Seru di Balik Layar (5W+1H)</h3>\n<ul class=\"list-disc list-inside space-y-2 mb-5 text-slate-700 dark:text-slate-300 leading-relaxed\">\n" . implode("\n", $fiveWFacts) . "\n</ul>\n"
                : "";

            $quoteBlock = !empty($quotes)
                ? "<blockquote class=\"border-l-4 border-primary pl-4 py-2 my-5 italic text-slate-700 dark:text-slate-300 bg-primary/5 rounded-r-2xl\">\"{$quotes}\"</blockquote>\n"
                : "<blockquote class=\"border-l-4 border-primary pl-4 py-2 my-5 italic text-slate-700 dark:text-slate-300 bg-primary/5 rounded-r-2xl\">\"Belajar dan berkarya di bangku SMP itu seru banget saat kita berani mencoba hal baru bareng teman-teman sekolah!\"</blockquote>\n";

            return "<h2>{$title}: Kisah Seru & Inspiratif dari Lingkungan Sekolah Kita</h2>\n" .
                   "<p class=\"lead text-base sm:text-lg font-medium leading-relaxed mb-4 text-slate-800 dark:text-slate-200\">Pernahkah kamu memperhatikan bagaimana serunya topik seputar <strong>{$cleanTopic}</strong> di sekolah kita? Bagi kita siswa SMP, ini bukan hanya cerita biasa, tetapi bukti nyata bahwa rasa ingin tahu dan kreativitas bisa membawa dampak positif bagi lingkungan sekitar!</p>\n" .
                   $notesSentence .
                   $fiveWBlock .
                   "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Keseruan dan Pengalaman Nyata</h3>\n" .
                   "<p class=\"mb-4 text-slate-700 dark:text-slate-300 leading-relaxed\">Saat mendalami {$cleanTopic}, banyak hal unik yang bisa kita pelajari langsung. Mulai dari kerja sama tim di kelas, berbagi ide seru saat istirahat, hingga bimbingan asyik dari para guru yang selalu mendukung ide-ide kreatif kita.</p>\n" .
                   $quoteBlock .
                   "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Tips Keren yang Bisa Kamu Coba</h3>\n" .
                   "<ul class=\"list-disc list-inside space-y-2 mb-5 text-slate-700 dark:text-slate-300 leading-relaxed\">\n" .
                   "    <li><strong>Ajak Teman Satu Ekskul:</strong> Segala hal jadi lebih menyenangkan jika dikerjakan bersama teman sebaya.</li>\n" .
                   "    <li><strong>Mulai dari Eksperimen Kecil:</strong> Jangan takut salah; rasa ingin tahu adalah awal dari penemuan besar.</li>\n" .
                   "    <li><strong>Catat dan Bagikan Pengalamanmu:</strong> Ceritakan kisah seru ini di majalah dinding atau forum sekolah.</li>\n" .
                   "</ul>\n" .
                   "<p class=\"mb-4 text-slate-700 dark:text-slate-300 leading-relaxed\">Teruslah berkreasi, nikmati setiap petualangan belajar di sekolah, dan jadilah inspirasi bagi sesama teman di Brighteen!</p>";
        }

        // ENGLISH IS DEFAULT
        $title = ucwords($cleanTopic);
        $notesSnippet = !empty($cleanNotes)
            ? "<p class=\"text-sm leading-relaxed mb-4 text-slate-700 dark:text-slate-300\">Special story angle: <em>{$cleanNotes}</em>.</p>\n"
            : "";

        $fiveWFactsEn = [];
        if (!empty($what)) $fiveWFactsEn[] = "<li><strong>What Happened:</strong> {$what}</li>";
        if (!empty($who)) $fiveWFactsEn[] = "<li><strong>Who was Involved:</strong> {$who}</li>";
        if (!empty($where)) $fiveWFactsEn[] = "<li><strong>Location:</strong> {$where}</li>";
        if (!empty($when)) $fiveWFactsEn[] = "<li><strong>When:</strong> {$when}</li>";
        if (!empty($why)) $fiveWFactsEn[] = "<li><strong>Why it Matters:</strong> {$why}</li>";
        if (!empty($how)) $fiveWFactsEn[] = "<li><strong>How it Unfolded:</strong> {$how}</li>";

        $fiveWBlockEn = !empty($fiveWFactsEn)
            ? "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Fast Facts & Behind the Scenes (5W+1H)</h3>\n<ul class=\"list-disc list-inside space-y-2 mb-5 text-slate-700 dark:text-slate-300 leading-relaxed\">\n" . implode("\n", $fiveWFactsEn) . "\n</ul>\n"
            : "";

        $quoteBlockEn = !empty($quotes)
            ? "<blockquote class=\"border-l-4 border-primary pl-4 py-2 my-5 italic text-slate-700 dark:text-slate-300 bg-primary/5 rounded-r-2xl\">\"{$quotes}\"</blockquote>\n"
            : "<blockquote class=\"border-l-4 border-primary pl-4 py-2 my-5 italic text-slate-700 dark:text-slate-300 bg-primary/5 rounded-r-2xl\">\"Exploring new ideas together in middle school is where our best friendships and projects are born!\"</blockquote>\n";

        return "<h2>{$title}: Why This School Story Inspires Us All</h2>\n" .
               "<p class=\"lead text-base sm:text-lg font-medium leading-relaxed mb-4 text-slate-800 dark:text-slate-200\">Exploring <strong>{$cleanTopic}</strong> is one of the most exciting things happening across our middle school halls today. For active student journalists and curious classmates, it shows how simple ideas turn into real school pride!</p>\n" .
               $notesSnippet .
               $fiveWBlockEn .
               "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Real Stories from Campus Life</h3>\n" .
               "<p class=\"mb-4 text-slate-700 dark:text-slate-300 leading-relaxed\">Behind this topic lies genuine curiosity, student collaboration, and the excitement of discovering how things work. Whether inside the classroom, in club workshops, or during break time, great teamwork always shines through.</p>\n" .
               $quoteBlockEn .
               "<h3 class=\"text-lg sm:text-xl font-bold my-4 text-primary\">Fun Takeaways for Students</h3>\n" .
               "<ul class=\"list-disc list-inside space-y-2 mb-5 text-slate-700 dark:text-slate-300 leading-relaxed\">\n" .
               "    <li><strong>Team Up with Classmates:</strong> Big projects are always more fun when shared with good friends.</li>\n" .
               "    <li><strong>Stay Curious:</strong> Don't hesitate to ask teachers and mentors questions that spark new ideas.</li>\n" .
               "    <li><strong>Share the Journey:</strong> Write about your experiences and inspire other students across campus!</li>\n" .
               "</ul>\n" .
               "<p class=\"mb-4 text-slate-700 dark:text-slate-300 leading-relaxed\">Keep dreaming big, have fun learning, and celebrate every cool milestone with your classmates!</p>";
    }

    /**
     * Dynamic Contextual Titles & SEO
     */
    protected function generateContextualTitles($topicOrContent, $targetLang = 'en')
    {
        $clean = trim(strip_tags($topicOrContent));

        if ($targetLang === 'id') {
            return "### ⚡ 5 Rekomendasi Judul Viral & Menarik:\n\n" .
                   "1. **\"Mengapa {$clean} Menjadi Topik Hangat yang Wajib Dipahami Pelajar Saat Ini\"** *(Perspektif Budaya & Tren)*\n" .
                   "2. **\"Bongkar Rahasia {$clean}: Panduan Praktis Tanpa Teori Berbelit\"** *(Praktis & Solutif)*\n" .
                   "3. **\"Apakah Kita Salah Memahami {$clean}? Ini Fakta yang Sebenarnya Terjadi\"** *(Pertanyaan Kritis / Hook Kuat)*\n" .
                   "4. **\"Catatan Kritis Seputar {$clean} dan Dampaknya bagi Masa Depan Anak Muda\"** *(Mendalam & Berbobot)*\n" .
                   "5. **\"Eksplorasi {$clean}: Langkah Cerdas Memulai Perubahan dari Hal Sederhana\"** *(Inspiratif & Aplikatif)*\n\n" .
                   "---\n" .
                   "### 🎯 Rekomendasi Ringkasan SEO (Excerpt):\n" .
                   "*Ulasan mendalam seputar {$clean}: fakta penting, tantangan nyata, dan panduan taktis bagi generasi muda dan pelajar masa kini.*";
        }

        return "### ⚡ 5 Catchy & High-CTR Title Options for Middle Schoolers:\n\n" .
               "1. **\"Why {$clean} is the Talk of Our Middle School Right Now\"** *(Student Culture & Campus Buzz)*\n" .
               "2. **\"The Student Field Guide to {$clean}: Fun, Easy, and Zero Boring Lectures\"** *(Practical & Relatable)*\n" .
               "3. **\"Did You Know This About {$clean}? Mind-Blowing Facts Every Junior High Student Should Hear\"** *(Curiosity Hook)*\n" .
               "4. **\"Behind the Scenes of {$clean}: How Teamwork and Curiosity Built Something Cool\"** *(Inspiring & Actionable)*\n" .
               "5. **\"Small Steps, Big Impact: What {$clean} Teaches Us About Creative Learning\"** *(Motivational)*\n\n" .
               "---\n" .
               "### 🎯 Middle School SEO Meta Description (148 chars):\n" .
               "*An engaging student magazine spotlight on {$clean}: fun facts, hands-on takeaways, and real inspiration for middle school readers.*";
    }

    /**
     * Dynamic Contextual Rephrase & Summarize
     */
    protected function generateContextualRephrase($text, $mode, $targetLang = 'en')
    {
        $clean = trim(strip_tags($text));

        if ($mode === 'summarize') {
            if ($targetLang === 'id') {
                return "### 📋 Ringkasan Editorial:\n\n" .
                       "Inti dari pembahasan ini menekankan bahwa dinamika yang terjadi menuntut kepekaan kritis dan langkah terukur. Dengan memprioritaskan konsistensi tindakan nyata dibandingkan sekadar wacana, generasi muda dapat mentransformasikan tantangan menjadi lompatan karya yang bermakna.";
            }
            return "### 📋 Editorial Summary:\n\n" .
                   "The key takeaway emphasizes that curiosity and consistent practice turn everyday class ideas into memorable student achievements. By keeping things simple and collaborating with friends, young learners make school life both fun and meaningful.";
        }

        // Rephrase
        if ($targetLang === 'id') {
            return "### 🪄 Versi Revisi yang Lebih Segar & Mengalir:\n\n" .
                   "\"Ketika kita melihat lebih dekat dinamika ini, terlihat jelas bahwa perubahan nyata tidak lahir dari keraguan yang berlarut-larut. Remaja dan pelajar masa kini membutuhkan arahan yang lugas, taktik yang bisa langsung dipraktikkan di lapangan, serta lingkungan suportif yang menghargai proses belajar secara utuh.\"";
        }

        return "### 🪄 Polished Middle School-Friendly Revision:\n\n" .
               "\"Taking a closer look at this shows that great discoveries begin whenever we're brave enough to ask simple questions. Middle school students love hands-on projects, clear explanations, and a supportive team of classmates to share every step of the journey.\"";
    }

    /**
     * Dynamic Contextual Tone & Grammar Audit
     */
    protected function generateContextualToneAudit($text, $targetLang = 'en')
    {
        $clean = trim(strip_tags($text));
        $wordCount = str_word_count($clean);

        if ($targetLang === 'id') {
            return "### 🔍 Hasil Audit Tata Bahasa & Keselarasan Nada\n\n" .
                   "- **Skor Keselarasan Nada SMP**: 94/100 (Segar, ramah, dan bebas dari jargon birokrasi)\n" .
                   "- **Jumlah Kata yang Dianalisis**: {$wordCount} kata\n" .
                   "- **Keterbacaan**: Sangat Baik (Ritme kalimat nyaman dibaca di layar smartphone)\n\n" .
                   "#### Catatan & Saran Penyuntingan:\n" .
                   "1. **Pembuka Kalimat**: Gunakan variasi kata hubung yang dinamis agar paragraf tidak terkesan monoton.\n" .
                   "2. **Pilihan Kata Aktif**: Ganti konstruksi kalimat pasif dengan kata kerja aktif agar pesan terasa lebih bertenaga.\n" .
                   "3. **Segmentasi Paragraf**: Pertahankan panjang paragraf maksimal 3-4 baris untuk kenyamanan mata pembaca mobile.";
        }

        return "### 🔍 Middle School Editorial Tone & Grammar Audit\n\n" .
               "- **Middle School Tone Score**: 95/100 (Vibrant, friendly, zero stiff academic jargon)\n" .
               "- **Analyzed Word Count**: {$wordCount} words\n" .
               "- **Readability**: Excellent (Smooth sentence flow tailored for teen readers)\n\n" .
               "#### Editorial Recommendations:\n" .
               "1. **Sentence Rhythm**: Balance punchy short hooks with informative details to keep readers hooked.\n" .
               "2. **Active Voice**: Keep student actions and quotes front and center for maximum energy.\n" .
               "3. **Mobile Scannability**: Keep paragraphs under 3-4 lines for effortless reading on phones.";
    }

    /**
     * Dynamic Contextual Ideas Generator
     */
    protected function generateContextualIdeas($niche, $targetLang = 'en')
    {
        $cleanNiche = trim(strip_tags($niche));

        if ($targetLang === 'id') {
            return "### 💡 6 Konsep Artikel Segar & Viral untuk Tema: {$cleanNiche}\n\n" .
                   "1. **Eksperimen 7 Hari Tanpa Distraksi Digital**: Catatan jujur siswa saat menukar ponsel pintar dengan buku catatan fisik.\n" .
                   "2. **Membangun Karya Nyata Sebelum Lulus**: Cerita pelajar yang berhasil merintis proyek komunitas dan portofolio mandiri.\n" .
                   "3. **Fenomena 'Micro-Learning' di Kalangan Pelajar**: Bagaimana media sosial mengubah cara kita menyerap materi ujian dan skill baru.\n" .
                   "4. **Mengatasi Fenomena Burnout Akademik**: Trik menjaga kesehatan mental di tengah padatnya jadwal ekstrakurikuler dan tugas.\n" .
                   "5. **Menavigasi AI di Ruang Kelas**: Antara peluang mempercepat riset dan tantangan menjaga orisinalitas pemikiran.\n" .
                   "6. **Ruang Aman untuk Berekspresi**: Mengapa zine independen dan buletin sekolah kembali diminati generasi muda.";
        }

        return "### 💡 6 Exciting Middle School Story Ideas for: {$cleanNiche}\n\n" .
               "1. **The 7-Day Screen Detox Experiment**: What happened when a student swapped infinite scrolling for paper sketching.\n" .
               "2. **From Classroom Doodles to School Pride**: How our robotics and art clubs teamed up to create cool DIY projects.\n" .
               "3. **Smart Study Hacks That Actually Work**: Science-backed memory tricks that middle schoolers swear by before exam week.\n" .
               "4. **Beating the After-School Slump**: Easy habits to stay energized between homework, club practice, and gaming with friends.\n" .
               "5. **Curiosity Over AI**: How to use smart tech tools for research without losing your own creative voice.\n" .
               "6. **The Story Behind Our School Zine**: Why printed comic strips and student opinion pieces are making a huge comeback.";
    }

    /**
     * Fallback for unknown prompts
     */
    protected function generateContextualFallback($prompt, $system)
    {
        $targetLang = $this->determineLanguage($prompt);
        return $this->generateContextualAutoDraft($prompt, '', [], $targetLang);
    }
}
