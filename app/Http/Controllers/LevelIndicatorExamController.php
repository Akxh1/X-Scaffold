<?php

namespace App\Http\Controllers;

use App\Models\LevelIndicatorAttempt;
use App\Models\Module;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentModulePerformance;
use App\Services\MLPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Level Indicator Exam Controller
 * 
 * Handles the Level Indicator Exam flow:
 * - show(): Display exam landing page or previous results
 * - start(): Initialize exam with random questions
 * - submit(): Process answers, extract features, run ML prediction
 * - results(): Display results with SHAP explanations
 */
class LevelIndicatorExamController extends Controller
{
    protected MLPredictionService $mlService;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Show Level Indicator exam landing page.
     * Displays previous attempts if any, or start button if can attempt.
     */
    public function show(Module $module)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Student profile not found.');
        }
        
        // Get attempt history
        $attempts = LevelIndicatorAttempt::getAttemptHistory($student->id, $module->id);
        $attemptCount = $attempts->count();
        $maxAttempts = $module->max_level_indicator_attempts ?? 3;
        $canAttempt = LevelIndicatorAttempt::canAttempt($student->id, $module->id);
        $latestAttempt = $attempts->first();
        
        // Module styling
        $colorMap = [
            1 => 'from-red-500 to-rose-600',
            2 => 'from-blue-500 to-cyan-600',
            3 => 'from-green-500 to-emerald-600',
            4 => 'from-purple-500 to-violet-600',
            5 => 'from-amber-500 to-orange-600',
            6 => 'from-indigo-500 to-blue-600',
        ];
        
        $iconMap = [
            1 => 'fa-shield-halved',
            2 => 'fa-cloud',
            3 => 'fa-chart-line',
            4 => 'fa-network-wired',
            5 => 'fa-tasks',
            6 => 'fa-code',
        ];
        
        $moduleData = [
            'id' => $module->id,
            'title' => $module->name,
            'description' => $module->description,
            'questions_count' => $module->questions()->count(),
            'gradient' => $colorMap[$module->id] ?? 'from-gray-500 to-slate-600',
            'icon' => $iconMap[$module->id] ?? 'fa-book',
        ];
        
        return view('level-indicator.show', compact(
            'module',
            'moduleData',
            'attempts',
            'attemptCount',
            'maxAttempts',
            'canAttempt',
            'latestAttempt',
            'student'
        ));
    }

    /**
     * Start a new Level Indicator exam attempt.
     * Selects random 10 questions from the module's question bank.
     */
    public function start(Module $module)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Student profile not found.');
        }
        
        // Check if can attempt
        if (!LevelIndicatorAttempt::canAttempt($student->id, $module->id)) {
            return redirect()->route('level-indicator.show', $module)
                ->with('error', 'Maximum attempts reached for this module.');
        }
        
        // Get stratified questions: 3 hard + 7 easy/medium
        $hardQuestions = Question::where('module_id', $module->id)
            ->where('is_hard', true)
            ->with('answers')
            ->inRandomOrder()
            ->take(3)
            ->get();

        $easyMediumQuestions = Question::where('module_id', $module->id)
            ->where(function ($q) {
                $q->where('is_hard', false)->orWhereNull('is_hard');
            })
            ->with('answers')
            ->inRandomOrder()
            ->take(7)
            ->get();

        // Merge and shuffle so hard questions appear in random positions
        $questions = $hardQuestions->merge($easyMediumQuestions)->shuffle();

        if ($questions->count() < 1) {
            return redirect()->route('level-indicator.show', $module)
                ->with('error', 'No questions available for this module.');
        }
        
        // Get next attempt number
        $attemptNumber = LevelIndicatorAttempt::getNextAttemptNumber($student->id, $module->id);
        
        // Module styling
        $colorMap = [
            1 => 'from-red-500 to-rose-600',
            2 => 'from-blue-500 to-cyan-600',
            3 => 'from-green-500 to-emerald-600',
            4 => 'from-purple-500 to-violet-600',
            5 => 'from-amber-500 to-orange-600',
            6 => 'from-indigo-500 to-blue-600',
        ];
        
        $moduleData = [
            'id' => $module->id,
            'title' => $module->name,
            'gradient' => $colorMap[$module->id] ?? 'from-gray-500 to-slate-600',
        ];
        
        return view('level-indicator.index', compact(
            'module',
            'moduleData',
            'questions',
            'attemptNumber',
            'student'
        ));
    }

    /**
     * Submit the Level Indicator exam.
     * Processes answers, extracts 11 features, runs ML prediction.
     */
    public function submit(Request $request, Module $module)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json(['error' => 'Student profile not found.'], 404);
        }
        
        // Validate request
        $validated = $request->validate([
            'answers' => 'required|array',
            'features' => 'required|array',
            'question_ids' => 'required|array',
        ]);
        
        $answers = $validated['answers'];
        $features = $validated['features'];
        $questionIds = $validated['question_ids'];
        
        // Get questions with correct answers
        $questions = Question::whereIn('id', $questionIds)
            ->with('answers')
            ->get()
            ->keyBy('id');
        
        // Score the exam
        $totalQuestions = count($questionIds);
        $totalCorrect = 0;
        $hardCorrect = 0;
        $hardTotal = 0;
        $firstHalfCorrect = 0;
        $secondHalfCorrect = 0;
        $halfPoint = ceil($totalQuestions / 2);
        
        $answersData = [];
        
        foreach ($questionIds as $index => $questionId) {
            $question = $questions->get($questionId);
            if (!$question) continue;
            
            $userAnswer = $answers[$questionId] ?? null;
            $correctAnswer = $question->answers->where('is_correct', true)->first();
            
            $isCorrect = false;
            if ($question->type === 'true_false') {
                $isCorrect = $userAnswer === ($correctAnswer?->answer_text ?? '');
            } elseif ($question->type === 'mcq') {
                $isCorrect = (int) $userAnswer === (int) ($correctAnswer?->id ?? 0);
            } else {
                // fill_in_blank - case-insensitive comparison
                $isCorrect = strtolower(trim($userAnswer ?? '')) === strtolower(trim($correctAnswer?->answer_text ?? ''));
            }
            
            if ($isCorrect) {
                $totalCorrect++;
                if ($index < $halfPoint) {
                    $firstHalfCorrect++;
                } else {
                    $secondHalfCorrect++;
                }
            }
            
            // Track hard questions (is_hard flag)
            if ($question->is_hard) {
                $hardTotal++;
                if ($isCorrect) {
                    $hardCorrect++;
                }
            }
            
            $answersData[$questionId] = [
                'user_answer' => $userAnswer,
                'correct' => $isCorrect,
                'difficulty' => $question->difficulty ?? 2,
            ];
        }
        
        // Calculate performance trend (2nd half - 1st half accuracy)
        $firstHalfAcc = $halfPoint > 0 ? ($firstHalfCorrect / $halfPoint) * 100 : 0;
        $secondHalfCount = $totalQuestions - $halfPoint;
        $secondHalfAcc = $secondHalfCount > 0 ? ($secondHalfCorrect / $secondHalfCount) * 100 : 0;
        $performanceTrend = $secondHalfAcc - $firstHalfAcc;
        
        // Build final features array
        $scorePercentage = $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0;
        $hardQuestionAccuracy = $hardTotal > 0 ? ($hardCorrect / $hardTotal) * 100 : 50;
        
        // Get next attempt number
        $attemptNumber = LevelIndicatorAttempt::getNextAttemptNumber($student->id, $module->id);
        
        // Create the attempt record with extracted features
        $attempt = LevelIndicatorAttempt::create([
            'student_id' => $student->id,
            'module_id' => $module->id,
            'attempt_number' => $attemptNumber,
            // Tier 1: Core 6 Features
            'score_percentage' => round($scorePercentage, 2),
            'hard_question_accuracy' => round($hardQuestionAccuracy, 2),
            'hint_usage_percentage' => round($features['hint_usage_percentage'] ?? 0, 2),
            'avg_confidence' => round($features['avg_confidence'] ?? 3.0, 2),
            'answer_changes_rate' => round($features['answer_changes_rate'] ?? 0, 4),
            'tab_switches_rate' => round($features['tab_switches_rate'] ?? 0, 4),
            // Tier 2: 5 ML Predictor Features
            'avg_time_per_question' => round($features['avg_time_per_question'] ?? 60, 2),
            'review_percentage' => round($features['review_percentage'] ?? 0, 2),
            'avg_first_action_latency' => round($features['avg_first_action_latency'] ?? 3, 2),
            'clicks_per_question' => round($features['clicks_per_question'] ?? 4, 2),
            'performance_trend' => round($performanceTrend, 2),
            // Raw data
            'question_ids' => $questionIds,
            'answers_data' => $answersData,
        ]);
        
        // Create or update StudentModulePerformance record
        $performance = StudentModulePerformance::updateOrCreate(
            [
                'student_id' => $student->id,
                'module_id' => $module->id,
            ],
            [
                'score_percentage' => $attempt->score_percentage,
                'hard_question_accuracy' => $attempt->hard_question_accuracy,
                'hint_usage_percentage' => $attempt->hint_usage_percentage,
                'avg_confidence' => $attempt->avg_confidence,
                'answer_changes_rate' => $attempt->answer_changes_rate,
                'tab_switches_rate' => $attempt->tab_switches_rate,
                'avg_time_per_question' => $attempt->avg_time_per_question,
                'review_percentage' => $attempt->review_percentage,
                'avg_first_action_latency' => $attempt->avg_first_action_latency,
                'clicks_per_question' => $attempt->clicks_per_question,
                'performance_trend' => $attempt->performance_trend,
            ]
        );
        
        // Run ML prediction with fallback
        $performance = $this->mlService->predictWithFallback($performance);
        
        // Copy ML results back to attempt record
        $attempt->update([
            'learning_mastery_score' => $performance->learning_mastery_score,
            'mastery_level' => $performance->mastery_level,
            'ml_prediction_confidence' => $performance->ml_prediction_confidence,
            'shap_values' => $performance->shap_values,
            'xai_explanation' => $performance->xai_explanation,
            'top_positive_factors' => $performance->top_positive_factors,
            'top_negative_factors' => $performance->top_negative_factors,
            'prediction_source' => $performance->prediction_source ?? 'local_fallback',
        ]);
        
        Log::info('Level Indicator Exam completed', [
            'student_id' => $student->id,
            'module_id' => $module->id,
            'attempt' => $attemptNumber,
            'score' => $scorePercentage,
            'lms' => $performance->learning_mastery_score,
            'level' => $performance->mastery_level,
        ]);
        
        return response()->json([
            'success' => true,
            'redirect' => route('level-indicator.results', [$module, $attempt]),
        ]);
    }

    /**
     * Display results for a specific attempt.
     * Shows LMS score, mastery level, and full SHAP breakdown.
     */
    public function results(Module $module, LevelIndicatorAttempt $attempt)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        // Verify this attempt belongs to the current student
        if (!$student || $attempt->student_id !== $student->id) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Unauthorized access to results.');
        }
        
        // Get all attempts for this module
        $attempts = LevelIndicatorAttempt::getAttemptHistory($student->id, $module->id);
        $canAttempt = LevelIndicatorAttempt::canAttempt($student->id, $module->id);
        $maxAttempts = $module->max_level_indicator_attempts ?? 3;
        
        // Module styling
        $colorMap = [
            1 => 'from-red-500 to-rose-600',
            2 => 'from-blue-500 to-cyan-600',
            3 => 'from-green-500 to-emerald-600',
            4 => 'from-purple-500 to-violet-600',
            5 => 'from-amber-500 to-orange-600',
            6 => 'from-indigo-500 to-blue-600',
        ];
        
        $moduleData = [
            'id' => $module->id,
            'title' => $module->name,
            'gradient' => $colorMap[$module->id] ?? 'from-gray-500 to-slate-600',
        ];
        
        // Get mastery badge
        $badge = $attempt->getMasteryBadge();
        
        // Parse SHAP values for display
        $shapValues = $attempt->shap_values ?? [];
        
        return view('level-indicator.results', compact(
            'module',
            'moduleData',
            'attempt',
            'attempts',
            'canAttempt',
            'maxAttempts',
            'badge',
            'shapValues',
            'student'
        ));
    }
}
