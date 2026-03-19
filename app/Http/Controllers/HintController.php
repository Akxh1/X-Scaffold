<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\StudentModulePerformance;
use App\Models\Question;
use App\Services\MLPredictionService;

class HintController extends Controller
{
    protected MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Generate an adaptive hint based on the question and student's ML-predicted level.
     * 
     * Three distinct modes:
     * 1. DIAGNOSTIC  → Fixed L2 hints (collecting data, no prior context)
     * 2. GENERIC     → One-liner hint (no Level Indicator data exists)
     * 3. PERSONALIZED → Rich adaptive hint using SHAP/XAI from StudentModulePerformance
     */
    public function generate(Request $request)
    {
        // --- 1. Get Inputs ---
        $question = $request->input('question_text');
        $moduleId = $request->input('module_id');
        $questionId = $request->input('question_id');
        $isDiagnostic = $request->input('is_diagnostic', false);

        // --- 2. Validate Input ---
        if (empty($question)) {
            return response()->json(['hint' => '<p>Error: No question text provided.</p>'], 400);
        }

        // --- 3. Determine Hint Mode ---
        $hintMode = 'generic'; // default
        $studentProfile = null;

        if ($isDiagnostic) {
            $hintMode = 'diagnostic';
            $promptText = $this->buildDiagnosticPrompt($question);
        } else {
            $xaiData = $this->getXAIContext($moduleId);

            if ($xaiData['is_real_data']) {
                $hintMode = 'personalized';
                $studentProfile = $xaiData['student_profile'];
                $promptText = $this->buildPersonalizedPrompt($question, $xaiData);
            } else {
                $hintMode = 'generic';
                $promptText = $this->buildGenericOneLinerPrompt($question);
            }
        }

        // --- 4. Call Gemini API (with retry for rate limits) ---
        $apiKey = env('GEMINI_INSIGHTS_API');
        $apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash-lite:generateContent?key={$apiKey}";

        $hasRealData = isset($xaiData) ? $xaiData['is_real_data'] : false;

        Log::info('Hint Generation Request:', [
            'question' => substr($question, 0, 100),
            'hint_mode' => $hintMode,
            'has_real_xai' => $hasRealData
        ]);

        // Retry up to 3 times with backoff for 429 rate limits
        // Token budget: L1=80, L2=120, L3=200, L4=350, generic=80, diagnostic=200
        $hintLevel = isset($xaiData) ? ($xaiData['hint_level'] ?? 3) : 3;
        $maxTokens = match(true) {
            $hintMode === 'generic' => 80,
            $hintMode === 'diagnostic' => 200,
            $hintLevel === 1 => 80,
            $hintLevel === 2 => 120,
            $hintLevel === 3 => 200,
            $hintLevel === 4 => 350,
            default => 200,
        };

        $response = null;
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($apiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $promptText]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $hintMode === 'generic' ? 0.5 : 0.7,
                    'maxOutputTokens' => $maxTokens,
                ]
            ]);

            if ($response->successful()) {
                break;
            }

            if ($response->status() === 429 && $attempt < $maxRetries) {
                $delay = $attempt * 2;
                Log::warning("Gemini API rate limited (attempt {$attempt}/{$maxRetries}), retrying in {$delay}s...");
                sleep($delay);
                continue;
            }

            break;
        }

        // --- 5. Handle API Response ---
        if (!$response || $response->failed()) {
            Log::error('Gemini API Error (after retries):', [
                'status' => $response?->status(),
                'body' => $response?->body()
            ]);

            $fallbackHint = $this->generateFallbackHint($question, $hintMode === 'personalized' ? ($xaiData['hint_level'] ?? 2) : 2);
            return response()->json([
                'hint' => $fallbackHint,
                'is_fallback' => true,
                'hint_mode' => $hintMode,
                'student_profile' => $studentProfile,
            ]);
        }

        Log::info('Gemini API Response received');

        $text = $response->json('candidates.0.content.parts.0.text');

        if (empty($text)) {
            Log::warning('Gemini API returned empty text:', $response->json());
            $fallbackHint = $this->generateFallbackHint($question, 2);
            return response()->json([
                'hint' => $fallbackHint,
                'is_fallback' => true,
                'hint_mode' => $hintMode,
                'student_profile' => $studentProfile,
            ]);
        }

        // --- 6. Clean and Return ---
        $cleanedText = $this->cleanHintOutput($text);

        // For At Risk (L4): include correct answer directly in the hint
        $correctAnswer = null;
        if ($questionId && isset($xaiData) && ($xaiData['hint_level'] ?? 0) === 4) {
            $correctAnswer = $this->getCorrectAnswer($questionId);
            if ($correctAnswer) {
                $cleanedText .= '<div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-lg">'
                    . '<p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1"><i class="fas fa-check-circle mr-1"></i>Answer</p>'
                    . '<p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">' . e($correctAnswer) . '</p>'
                    . '</div>';
            }
        }

        return response()->json([
            'hint' => $cleanedText,
            'is_fallback' => false,
            'hint_mode' => $hintMode,
            'hint_level' => isset($xaiData) ? ($xaiData['hint_level'] ?? null) : null,
            'student_profile' => $studentProfile,
        ]);
    }

    /**
     * Get XAI context from database. Returns full student profile when available.
     */
    protected function getXAIContext(?int $moduleId): array
    {
        $result = [
            'hint_level' => 2,
            'student_level' => 'Developing (L2)',
            'xai_analysis' => '',
            'is_real_data' => false,
            'student_profile' => null,
            'performance' => null,
        ];

        $user = Auth::user();
        if (!$user || !$moduleId) {
            return $result;
        }

        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return $result;
        }

        $performance = StudentModulePerformance::where('student_id', $student->id)
            ->where('module_id', $moduleId)
            ->first();

        if (!$performance || !$performance->mastery_level) {
            return $result;
        }

        // --- Real data found ---
        $hint_level = $this->masteryNameToHintLevel($performance->mastery_level);
        $level_names = [1 => 'Advanced', 2 => 'Proficient', 3 => 'Developing', 4 => 'At Risk'];

        // Build XAI analysis string for prompt
        $xai_analysis = '';
        if ($performance->xai_explanation) {
            $xai_analysis = $performance->xai_explanation;
        } elseif ($performance->shap_values) {
            $xai_analysis = $this->formatShapValues($performance->shap_values, $performance);
        } else {
            $xai_analysis = $this->generateLMSBasedAnalysis($performance);
        }

        // Build student profile for frontend display
        $studentProfile = [
            'mastery_level' => $performance->mastery_level,
            'lms_score' => round($performance->learning_mastery_score, 1),
            'ml_confidence' => round($performance->ml_prediction_confidence * 100, 0),
            'score_percentage' => round($performance->score_percentage, 1),
            'avg_confidence' => round($performance->avg_confidence, 1),
            'hint_usage' => round($performance->hint_usage_percentage, 1),
            'top_strengths' => $performance->top_positive_factors,
            'top_weaknesses' => $performance->top_negative_factors,
        ];

        return [
            'hint_level' => $hint_level,
            'student_level' => $level_names[$hint_level] ?? 'Developing (L2)',
            'xai_analysis' => $xai_analysis,
            'is_real_data' => true,
            'student_profile' => $studentProfile,
            'performance' => $performance,
        ];
    }

    // ================================================================
    // PROMPT BUILDERS
    // ================================================================

    /**
     * Build a one-liner prompt for students WITHOUT Level Indicator data.
     * Produces a brief, generic hint — no adaptive scaffolding.
     */
    protected function buildGenericOneLinerPrompt(string $question): string
    {
        return "
You are a helpful tutor. A student is practising and has NOT completed any diagnostic assessment, so you have no data about their level.

=== QUESTION ===
\"{$question}\"

=== INSTRUCTIONS ===
Provide a SHORT hint as exactly 2 bullet points:
• Bullet 1: A guiding question that nudges the student toward the right approach
• Bullet 2: The key concept or topic they should revisit

Do NOT reveal the answer. Keep it under 30 words total.
Output as an HTML unordered list (<ul><li>). No markdown.

Generate the hint now:
";
    }

    /**
     * Build a diagnostic prompt (Level Indicator exam — collecting data).
     * Fixed moderate hints since we're gathering data, not personalizing.
     */
    protected function buildDiagnosticPrompt(string $question): string
    {
        return "
You are an expert tutor. This student is taking a DIAGNOSTIC assessment (Level Indicator Exam) so you do NOT know their level yet.

=== QUESTION ===
\"{$question}\"

=== INSTRUCTIONS ===
Provide a balanced hint as a short numbered list (3 steps max):
1. The key concept or approach to consider
2. A common mistake to avoid
3. The first step to get started (without solving it)

Keep each step to ONE sentence. Maximum 60 words total.
Output as an HTML ordered list (<ol><li>). No markdown.

Generate the hint now:
";
    }

    /**
     * Build a rich personalized prompt using all SHAP/XAI data.
     * This is the full adaptive scaffolding experience.
     */
    protected function buildPersonalizedPrompt(string $question, array $xaiData): string
    {
        $profile = $xaiData['student_profile'];
        $performance = $xaiData['performance'];
        $studentLevel = $xaiData['student_level'];
        $hintLevel = $xaiData['hint_level'];
        $xaiAnalysis = $xaiData['xai_analysis'];

        // Build detailed behavioral profile for the LLM
        $behavioralContext = sprintf(
            "Score: %s%% | Hard Q Accuracy: %s%% | Confidence: %s/5 | Hint Usage: %s%% | " .
            "Answer Changes: %s/q | Tab Switches: %s/q | Time/Q: %ss | Review: %s%% | " .
            "First Action: %ss | Clicks/Q: %s | Performance Trend: %s",
            round($performance->score_percentage, 1),
            round($performance->hard_question_accuracy, 1),
            round($performance->avg_confidence, 1),
            round($performance->hint_usage_percentage, 1),
            round($performance->answer_changes_rate, 2),
            round($performance->tab_switches_rate, 2),
            round($performance->avg_time_per_question, 1),
            round($performance->review_percentage, 1),
            round($performance->avg_first_action_latency, 1),
            round($performance->clicks_per_question, 1),
            ($performance->performance_trend >= 0 ? '+' : '') . round($performance->performance_trend, 1)
        );

        $strengths = $profile['top_strengths'] ?? 'Not available';
        $weaknesses = $profile['top_weaknesses'] ?? 'Not available';

        // Select the scaffolding strategy for this level
        $strategy = $this->getScaffoldingStrategy($hintLevel);

        return "
You are an expert, empathetic tutor. You give structured, visually clear hints in clean HTML.

=== STUDENT PROFILE ===
- Level: {$studentLevel}
- LMS: {$profile['lms_score']}/100 | Score: {$profile['score_percentage']}%
- Strengths: {$strengths}
- Weaknesses: {$weaknesses}
- XAI: {$xaiAnalysis}

=== BEHAVIORAL DATA ===
{$behavioralContext}

=== QUESTION ===
\"{$question}\"

=== YOUR TASK ===
{$strategy}

=== STRICT RULES ===
1. Output ONLY clean HTML tags: <p>, <ul>, <ol>, <li>, <strong>, <em>. NO markdown.
2. NEVER use em dashes, en dashes, or the characters: \xE2\x80\x94 or \xE2\x80\x93. Use periods or colons instead.
3. NEVER output a wall of text. Always use lists or short paragraphs.
4. Reference student data naturally. Never say \"AI\", \"SHAP\", or \"model\".
5. Keep a warm, encouraging tone throughout.

Generate the hint now:
";
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    /**
     * Format SHAP values into natural language for the prompt.
     */
    protected function formatShapValues(array $shapValues, StudentModulePerformance $performance): string
    {
        $parts = [];

        $sorted = collect($shapValues)
            ->map(fn($v, $k) => ['name' => $k, 'value' => $v['value'] ?? 0])
            ->sortByDesc(fn($item) => abs($item['value']));

        $positive = $sorted->filter(fn($item) => $item['value'] > 0.03)->take(3);
        $negative = $sorted->filter(fn($item) => $item['value'] < -0.03)->take(3);

        if ($positive->isNotEmpty()) {
            $posText = $positive->map(fn($item) =>
                $this->humanizeFeature($item['name']) . " (+" . round($item['value'], 2) . ")"
            )->implode(', ');
            $parts[] = "Positive factors: {$posText}";
        }

        if ($negative->isNotEmpty()) {
            $negText = $negative->map(fn($item) =>
                $this->humanizeFeature($item['name']) . " (" . round($item['value'], 2) . ")"
            )->implode(', ');
            $parts[] = "Areas for improvement: {$negText}";
        }

        $parts[] = sprintf(
            "Current performance: %.1f%% score, %.1f%% hint usage, %.1f confidence",
            $performance->score_percentage,
            $performance->hint_usage_percentage,
            $performance->avg_confidence
        );

        return "Based on SHAP analysis: " . implode(". ", $parts) . ".";
    }

    /**
     * Generate XAI-style analysis from LMS components.
     */
    protected function generateLMSBasedAnalysis(StudentModulePerformance $p): string
    {
        $parts = [];

        if ($p->score_percentage >= 70) {
            $parts[] = "Score of {$p->score_percentage}% shows strong performance";
        } elseif ($p->score_percentage >= 50) {
            $parts[] = "Score of {$p->score_percentage}% indicates developing understanding";
        } else {
            $parts[] = "Score of {$p->score_percentage}% suggests need for additional support";
        }

        if ($p->hint_usage_percentage > 50) {
            $parts[] = "High hint usage ({$p->hint_usage_percentage}%) shows reliance on scaffolding";
        } elseif ($p->hint_usage_percentage > 20) {
            $parts[] = "Moderate hint usage ({$p->hint_usage_percentage}%) indicates balanced learning";
        } else {
            $parts[] = "Low hint usage ({$p->hint_usage_percentage}%) demonstrates independence";
        }

        $expectedConf = $p->score_percentage / 20;
        $confDiff = abs($p->avg_confidence - $expectedConf);
        if ($confDiff <= 1) {
            $parts[] = "Confidence ({$p->avg_confidence}/5) is well-calibrated";
        } elseif ($p->avg_confidence > $expectedConf) {
            $parts[] = "Confidence ({$p->avg_confidence}/5) may be slightly overestimated";
        } else {
            $parts[] = "Confidence ({$p->avg_confidence}/5) could be higher given performance";
        }

        if ($p->tab_switches_rate > 2) {
            $parts[] = "Focus patterns suggest some attention challenges";
        }

        return "Based on LMS analysis: " . implode(". ", $parts) . ".";
    }

    /**
     * Convert feature name to human-readable format.
     */
    protected function humanizeFeature(string $feature): string
    {
        $map = [
            'score_percentage' => 'Score',
            'hard_question_accuracy' => 'Hard Question Accuracy',
            'hint_usage_percentage' => 'Hint Usage',
            'avg_confidence' => 'Confidence',
            'answer_changes_rate' => 'Answer Stability',
            'tab_switches_rate' => 'Focus Rate',
            'avg_time_per_question' => 'Time per Question',
            'review_percentage' => 'Review Usage',
            'avg_first_action_latency' => 'Response Time',
            'clicks_per_question' => 'Engagement',
            'performance_trend' => 'Endurance Trend',
        ];

        return $map[$feature] ?? str_replace('_', ' ', ucfirst($feature));
    }

    /**
     * Map mastery level name to hint level.
     */
    protected function masteryNameToHintLevel(?string $masteryLevel): int
    {
        return match($masteryLevel) {
            'advanced' => 1,
            'proficient' => 2,
            'developing' => 3,
            'at_risk' => 4,
            default => 3
        };
    }

    /**
     * Get the scaffolding strategy prompt text for a given hint level.
     */
    protected function getScaffoldingStrategy(int $level): string
    {
        return match($level) {
            // L1: Advanced — Socratic, minimal
            1 => "
Produce a SINGLE Socratic question (max 20 words) that pushes the student to think deeper.
Wrap it in: <p><strong>[question]</strong></p>
Then add ONE sentence of acknowledgment referencing a strength.
Wrap it in: <p><em>[acknowledgment]</em></p>
Total: max 2 HTML elements. Do NOT give any steps or explanations.",

            // L2: Proficient — Guiding bullets
            2 => "
Produce EXACTLY 2 bullet points:
<ul>
<li><strong>Think about:</strong> [A guiding question that nudges toward the right approach]</li>
<li><strong>Key concept:</strong> [The specific topic or formula to revisit]</li>
</ul>
Max 40 words total. Do NOT reveal the answer or give step-by-step solutions.",

            // L3: Developing — Numbered steps
            3 => "
Produce a structured hint with EXACTLY 3 numbered steps:
<ol>
<li><strong>Recall:</strong> [The key concept or definition needed]</li>
<li><strong>Watch out:</strong> [A common mistake to avoid]</li>
<li><strong>Start here:</strong> [The first concrete action to take, without solving it]</li>
</ol>
Then add one line: <p><em>[brief encouragement]</em></p>
Max 80 words. Use simple, clear language.",

            // L4: At Risk — Full concept explanation with answer
            4 => "
Produce a comprehensive, easy-to-follow hint in this exact structure:

<p><strong>Concept:</strong> [Explain the underlying concept in 2-3 simple sentences using everyday language or analogies]</p>

<p><strong>How to solve this:</strong></p>
<ol>
<li>[Step 1: what to do and why, in simple words]</li>
<li>[Step 2: what to do and why]</li>
<li>[Step 3: what to do and why]</li>
</ol>

<p><strong>The answer:</strong> [State the correct answer clearly with a one-sentence explanation of why it is correct]</p>

<p><em>[Warm encouragement referencing their effort]</em></p>

Max 200 words. Use the simplest possible language. Write as if explaining to someone encountering this topic for the first time.",

            default => "
Produce 3 numbered steps as an HTML ordered list.
Max 80 words. Use clear, structured HTML.",
        };
    }

    /**
     * Generate a meaningful fallback hint when Gemini API is unavailable.
     */
    protected function generateFallbackHint(string $question, int $hintLevel): string
    {
        $questionLower = strtolower($question);

        // Topic-specific guidance mapped to structured hints
        $topicData = [
            'set' => ['concept' => 'Set Operations', 'question' => 'Which set operation is being asked for — union, intersection, or difference?', 'step1' => 'Identify the elements in each set', 'step2' => 'Apply the relevant set operation', 'step3' => 'Write out the resulting set'],
            'probability' => ['concept' => 'Probability', 'question' => 'What is the total number of possible outcomes here?', 'step1' => 'Count the total outcomes in the sample space', 'step2' => 'Count the favorable outcomes', 'step3' => 'Apply P(E) = favorable / total'],
            'function' => ['concept' => 'Functions', 'question' => 'What is the domain and range of this function?', 'step1' => 'Identify the input (domain) and output (range)', 'step2' => 'Substitute a value to trace the function', 'step3' => 'Check if the result matches the expected output'],
            'graph' => ['concept' => 'Graph Theory', 'question' => 'How many vertices and edges does the graph have?', 'step1' => 'List the vertices and edges', 'step2' => 'Check for the property being asked about', 'step3' => 'Verify with the definition'],
            'logic' => ['concept' => 'Propositional Logic', 'question' => 'Can you break this into smaller propositions?', 'step1' => 'Identify each proposition', 'step2' => 'Assign truth values', 'step3' => 'Evaluate using the logical connectives'],
            'relation' => ['concept' => 'Relations', 'question' => 'Which property is being tested — reflexive, symmetric, or transitive?', 'step1' => 'Check reflexive: does a→a hold for all elements?', 'step2' => 'Check symmetric: if a→b, does b→a hold?', 'step3' => 'Check transitive: if a→b and b→c, does a→c hold?'],
            'matrix' => ['concept' => 'Matrix Operations', 'question' => 'What are the dimensions of the matrices involved?', 'step1' => 'Confirm the dimensions allow the operation', 'step2' => 'Work through element by element', 'step3' => 'Double-check your arithmetic'],
            'tree' => ['concept' => 'Trees', 'question' => 'What is the height and how many leaf nodes are there?', 'step1' => 'Identify the root node', 'step2' => 'Trace the path to the relevant node', 'step3' => 'Count edges or levels as needed'],
            'boolean' => ['concept' => 'Boolean Algebra', 'question' => 'Can you apply De Morgan\'s law or distribution here?', 'step1' => 'Write the expression in standard form', 'step2' => 'Apply the relevant Boolean law', 'step3' => 'Simplify step by step'],
            'proof' => ['concept' => 'Mathematical Proof', 'question' => 'What exactly do you need to show?', 'step1' => 'State your assumptions clearly', 'step2' => 'Identify the proof technique (direct, contradiction, induction)', 'step3' => 'Build the argument step by step'],
        ];

        // Find matching topic
        $matched = null;
        foreach ($topicData as $keyword => $data) {
            if (str_contains($questionLower, $keyword)) {
                $matched = $data;
                break;
            }
        }

        // Default fallback
        if (!$matched) {
            $matched = [
                'concept' => 'the core concept',
                'question' => 'What are the key terms in this question and what concept do they relate to?',
                'step1' => 'Re-read the question and underline key terms',
                'step2' => 'Recall the relevant definition or formula',
                'step3' => 'Apply it to the specific values given',
            ];
        }

        // Format based on hint level (4 tiers)
        return match($hintLevel) {
            // L1 (Advanced): Socratic question only
            1 => '<p><strong>' . $matched['question'] . '</strong></p>'
                . '<p><em>You have the skills for this. Trust your knowledge.</em></p>',

            // L2 (Proficient): 2 guiding bullets
            2 => '<ul>'
                . '<li><strong>Think about:</strong> ' . $matched['question'] . '</li>'
                . '<li><strong>Key concept:</strong> ' . $matched['concept'] . '</li>'
                . '</ul>',

            // L4 (At Risk): Full walkthrough with answer
            4 => '<p><strong>Concept:</strong> This question is about <strong>' . $matched['concept'] . '</strong>.</p>'
                . '<p><strong>How to solve this:</strong></p>'
                . '<ol>'
                . '<li>' . $matched['step1'] . '</li>'
                . '<li>' . $matched['step2'] . '</li>'
                . '<li>' . $matched['step3'] . '</li>'
                . '</ol>'
                . '<p><em>Take it one step at a time. You are making progress!</em></p>',

            // L3 (Developing): 3 numbered steps
            default => '<ol>'
                . '<li><strong>Recall:</strong> ' . $matched['concept'] . '</li>'
                . '<li><strong>Watch out:</strong> ' . $matched['step1'] . '</li>'
                . '<li><strong>Start here:</strong> ' . $matched['step2'] . '</li>'
                . '</ol>'
                . '<p><em>Keep going, you are building understanding!</em></p>',
        };
    }

    /**
     * Clean the LLM output to ensure proper HTML format.
     */
    protected function cleanHintOutput(string $text): string
    {
        // Strip code fence wrappers
        $text = preg_replace('/^```html\s*|\s*```$/s', '', $text);

        // Remove em dashes and en dashes (AI-style punctuation)
        $text = str_replace(['—', '–'], ['. ', '. '], $text);
        $text = preg_replace('/ \. /', '. ', $text); // clean double spaces

        // Convert markdown bold/italic to HTML
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

        // Convert markdown numbered lists (1. item) to HTML <ol><li>
        if (preg_match('/^\d+\.\s/m', $text) && !str_contains($text, '<ol>')) {
            $text = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $text);
            $text = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ol>$0</ol>', $text);
        }

        // Convert markdown bullet lists (- item or • item) to HTML <ul><li>
        if (preg_match('/^[\-•]\s/m', $text) && !str_contains($text, '<ul>')) {
            $text = preg_replace('/^[\-•]\s+(.+)$/m', '<li>$1</li>', $text);
            $text = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ul>$0</ul>', $text);
        }

        // Clean up any double-wrapped lists
        $text = preg_replace('/<ol>\s*<ol>/', '<ol>', $text);
        $text = preg_replace('/<\/ol>\s*<\/ol>/', '</ol>', $text);
        $text = preg_replace('/<ul>\s*<ul>/', '<ul>', $text);
        $text = preg_replace('/<\/ul>\s*<\/ul>/', '</ul>', $text);

        return trim($text);
    }

    /**
     * Look up the correct answer for a given question.
     */
    protected function getCorrectAnswer(int $questionId): ?string
    {
        $question = Question::with('answers')->find($questionId);
        if (!$question) return null;

        if ($question->type === 'true_false') {
            $correct = $question->answers->first(fn($a) => $a->is_correct);
            return $correct ? ($correct->answer_text ?: 'True') : null;
        }

        if ($question->type === 'fill_in_blank') {
            $correct = $question->answers->first(fn($a) => $a->is_correct);
            return $correct?->answer_text;
        }

        // MCQ
        $correct = $question->answers->first(fn($a) => $a->is_correct);
        return $correct?->answer_text;
    }
}