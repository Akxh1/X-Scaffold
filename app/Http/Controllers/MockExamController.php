<?php

namespace App\Http\Controllers;

use App\Models\MockExamAttempt;
use App\Models\Module;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentModulePerformance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Mock Exam Controller
 * 
 * Handles the Mock Exam flow — the "Act" part of Predict-Explain-Act.
 * Unlike the Level Indicator Exam, the Mock Exam:
 * - Has unlimited attempts (practice mode)
 * - Does NOT trigger ML re-prediction
 * - Uses adaptive (non-diagnostic) hints based on Level Indicator data
 * - Requires Level Indicator completion for personalized hints
 */
class MockExamController extends Controller
{
    /**
     * Show Mock Exam landing page.
     * Displays previous attempts and start button.
     * Shows disclaimer if student hasn't done the Level Indicator Exam.
     */
    public function show(Module $module)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Student profile not found.');
        }

        // Check if the student has Level Indicator data
        $performance = StudentModulePerformance::where('student_id', $student->id)
            ->where('module_id', $module->id)
            ->first();

        $hasLevelIndicatorData = $performance && $performance->mastery_level;

        // Get mock exam attempt history
        $attempts = MockExamAttempt::getAttemptHistory($student->id, $module->id);
        $attemptCount = $attempts->count();
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

        return view('mock-exam.show', compact(
            'module',
            'moduleData',
            'attempts',
            'attemptCount',
            'latestAttempt',
            'performance',
            'hasLevelIndicatorData',
            'student'
        ));
    }

    /**
     * Start a new Mock Exam attempt.
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

        // Check if the student has Level Indicator data for adaptive hints
        $performance = StudentModulePerformance::where('student_id', $student->id)
            ->where('module_id', $module->id)
            ->first();

        $hasLevelIndicatorData = $performance && $performance->mastery_level;

        // Determine hard question count based on student's mastery level
        $hardCount = 3; // default when no data
        if ($performance && $performance->mastery_level) {
            $hardCount = match($performance->mastery_level) {
                'advanced'   => 6, // Challenge strong students
                'proficient' => 4,
                'developing' => 2,
                'at_risk'    => 1, // Mostly easy/medium for struggling students
                default      => 3,
            };
        }
        $easyCount = 10 - $hardCount;

        // Get stratified questions: hard + easy/medium based on mastery
        $hardQuestions = Question::where('module_id', $module->id)
            ->where('is_hard', true)
            ->with('answers')
            ->inRandomOrder()
            ->take($hardCount)
            ->get();

        $easyMediumQuestions = Question::where('module_id', $module->id)
            ->where(function ($q) {
                $q->where('is_hard', false)->orWhereNull('is_hard');
            })
            ->with('answers')
            ->inRandomOrder()
            ->take($easyCount)
            ->get();

        // Merge and shuffle so hard questions appear in random positions
        $questions = $hardQuestions->merge($easyMediumQuestions)->shuffle();

        if ($questions->count() < 1) {
            return redirect()->route('mock-exam.show', $module)
                ->with('error', 'No questions available for this module.');
        }

        // Get next attempt number
        $attemptNumber = MockExamAttempt::getNextAttemptNumber($student->id, $module->id);

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

        return view('mock-exam.index', compact(
            'module',
            'moduleData',
            'questions',
            'attemptNumber',
            'student',
            'hasLevelIndicatorData'
        ));
    }

    /**
     * Submit the Mock Exam.
     * Processes answers, extracts features, saves attempt.
     * Does NOT run ML prediction — that's only for Level Indicator.
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
                // fill_in_blank
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
                'correct_answer_text' => $correctAnswer?->answer_text ?? '',
                'correct_answer_id' => $correctAnswer?->id ?? null,
            ];
        }

        // Calculate derived features
        $scorePercentage = $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0;
        $hardQuestionAccuracy = $hardTotal > 0 ? ($hardCorrect / $hardTotal) * 100 : 50;

        $firstHalfAcc = $halfPoint > 0 ? ($firstHalfCorrect / $halfPoint) * 100 : 0;
        $secondHalfCount = $totalQuestions - $halfPoint;
        $secondHalfAcc = $secondHalfCount > 0 ? ($secondHalfCorrect / $secondHalfCount) * 100 : 0;
        $performanceTrend = $secondHalfAcc - $firstHalfAcc;

        // Get next attempt number
        $attemptNumber = MockExamAttempt::getNextAttemptNumber($student->id, $module->id);

        // Create the attempt record
        $attempt = MockExamAttempt::create([
            'student_id' => $student->id,
            'module_id' => $module->id,
            'attempt_number' => $attemptNumber,
            'total_correct' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'score_percentage' => round($scorePercentage, 2),
            'hard_question_accuracy' => round($hardQuestionAccuracy, 2),
            'hint_usage_percentage' => round($features['hint_usage_percentage'] ?? 0, 2),
            'avg_confidence' => round($features['avg_confidence'] ?? 3.0, 2),
            'answer_changes_rate' => round($features['answer_changes_rate'] ?? 0, 4),
            'tab_switches_rate' => round($features['tab_switches_rate'] ?? 0, 4),
            'avg_time_per_question' => round($features['avg_time_per_question'] ?? 60, 2),
            'review_percentage' => round($features['review_percentage'] ?? 0, 2),
            'avg_first_action_latency' => round($features['avg_first_action_latency'] ?? 3, 2),
            'clicks_per_question' => round($features['clicks_per_question'] ?? 4, 2),
            'performance_trend' => round($performanceTrend, 2),
            'question_ids' => $questionIds,
            'answers_data' => $answersData,
        ]);

        Log::info('Mock Exam completed', [
            'student_id' => $student->id,
            'module_id' => $module->id,
            'attempt' => $attemptNumber,
            'score' => $scorePercentage,
            'total_correct' => $totalCorrect,
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('mock-exam.results', [$module, $attempt]),
        ]);
    }

    /**
     * Display results for a specific mock exam attempt.
     * Shows score, per-question breakdown, and behavioral features.
     */
    public function results(Module $module, MockExamAttempt $attempt)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        // Verify this attempt belongs to the current student
        if (!$student || $attempt->student_id !== $student->id) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Unauthorized access to results.');
        }

        // Get all attempts for history
        $attempts = MockExamAttempt::getAttemptHistory($student->id, $module->id);

        // Get questions for per-question breakdown
        $questionIds = $attempt->question_ids ?? [];
        $questions = Question::whereIn('id', $questionIds)
            ->with('answers')
            ->get()
            ->keyBy('id');

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

        // Get score badge
        $badge = $attempt->getScoreBadge();

        return view('mock-exam.results', compact(
            'module',
            'moduleData',
            'attempt',
            'attempts',
            'questions',
            'badge',
            'student'
        ));
    }
}
