<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\StudentModulePerformance;
use App\Models\Module;
use App\Models\Question;
use App\Models\Answer;

class DashboardController extends Controller
{
    /**
     * Instructor Dashboard - shows all students with their multi-module performance
     */
    public function index(Request $request)
    {
        // Get all modules for the filter
        $modules = Module::withCount(['studentPerformances', 'questions'])->get();
        
        // Get selected module filter (default: show all)
        $selectedModuleId = $request->get('module', 'all');
        
        // Get all students with their module performance data
        $students = Student::with(['modulePerformances.module', 'user'])->get();
        
        // Calculate overall statistics
        $totalStudents = $students->count();
        
        // All performance records
        $allPerformances = StudentModulePerformance::with(['student', 'module'])->get();
        
        // Students who have taken at least one exam
        $studentsWithAnyPerformance = $students->filter(fn($s) => $s->modulePerformances->isNotEmpty());
        $studentsWithoutAnyPerformance = $students->filter(fn($s) => $s->modulePerformances->isEmpty());
        
        // Calculate metrics
        $examTakenCount = $studentsWithAnyPerformance->count();
        $examPendingCount = $studentsWithoutAnyPerformance->count();
        $totalPerformanceRecords = $allPerformances->count();
        
        // LMS Statistics
        $averageLMS = $allPerformances->count() > 0 ? $allPerformances->avg('learning_mastery_score') : 0;
        
        // Mastery level distribution across all modules
        $masteryDistribution = [
            'advanced' => $allPerformances->where('mastery_level', 'advanced')->count(),
            'proficient' => $allPerformances->where('mastery_level', 'proficient')->count(),
            'developing' => $allPerformances->where('mastery_level', 'developing')->count(),
            'at_risk' => $allPerformances->where('mastery_level', 'at_risk')->count(),
        ];
        
        // Per-module statistics
        $moduleStats = $modules->map(function ($module) use ($allPerformances) {
            $modulePerfs = $allPerformances->where('module_id', $module->id);
            return [
                'id' => $module->id,
                'name' => $module->name,
                'student_count' => $modulePerfs->count(),
                'questions_count' => $module->questions_count,
                'avg_lms' => $modulePerfs->count() > 0 ? round($modulePerfs->avg('learning_mastery_score'), 1) : 0,
                'avg_score' => $modulePerfs->count() > 0 ? round($modulePerfs->avg('score_percentage'), 1) : 0,
            ];
        });
        
        // Average feature values for insights
        $featureAverages = [];
        if ($allPerformances->count() > 0) {
            $featureAverages = [
                'score_percentage' => round($allPerformances->avg('score_percentage'), 1),
                'hard_question_accuracy' => round($allPerformances->avg('hard_question_accuracy'), 1),
                'hint_usage_percentage' => round($allPerformances->avg('hint_usage_percentage'), 1),
                'avg_confidence' => round($allPerformances->avg('avg_confidence'), 2),
                'avg_time_per_question' => round($allPerformances->avg('avg_time_per_question'), 1),
                'review_percentage' => round($allPerformances->avg('review_percentage'), 1),
            ];
        }

        return view('dashboard', compact(
            'students',
            'modules',
            'selectedModuleId',
            'studentsWithAnyPerformance',
            'studentsWithoutAnyPerformance',
            'totalStudents',
            'examTakenCount',
            'examPendingCount',
            'totalPerformanceRecords',
            'averageLMS',
            'masteryDistribution',
            'moduleStats',
            'featureAverages',
            'allPerformances'
        ));
    }

    /**
     * Student Dashboard - shows modules available to the student with their performance
     */
    public function studentDashboard()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        // Fetch all modules with question counts
        $modules = Module::withCount('questions')->get();
        
        // Get student's performance data
        $studentPerformances = collect();
        if ($student) {
            $studentPerformances = StudentModulePerformance::where('student_id', $student->id)
                ->get()
                ->keyBy('module_id');
        }
        
        // Module styling maps
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
        
        // Enrich modules with student performance data
        $modules = $modules->map(function ($module) use ($studentPerformances, $colorMap, $iconMap) {
            $performance = $studentPerformances->get($module->id);
            $hasPerformance = !is_null($performance);
            
            return [
                'id' => $module->id,
                'title' => $module->name,
                'description' => $module->description,
                'questions_count' => $module->questions_count,
                'gradient' => $colorMap[$module->id] ?? 'from-gray-500 to-slate-600',
                'icon' => $iconMap[$module->id] ?? 'fa-book',
                // Performance data
                'has_performance' => $hasPerformance,
                'lms' => $hasPerformance ? round($performance->learning_mastery_score, 1) : null,
                'mastery_level' => $hasPerformance ? $performance->mastery_level : null,
                'score' => $hasPerformance ? round($performance->score_percentage, 1) : null,
                'level_indicator_completed' => $hasPerformance,
            ];
        });
        
        // Calculate overall stats
        $totalModules = $modules->count();
        $completedModules = $studentPerformances->count();
        $avgLMS = $studentPerformances->count() > 0 ? round($studentPerformances->avg('learning_mastery_score'), 1) : 0;
        $avgScore = $studentPerformances->count() > 0 ? round($studentPerformances->avg('score_percentage'), 1) : 0;

        return view('dashboard.student', compact(
            'modules',
            'student',
            'totalModules',
            'completedModules',
            'avgLMS',
            'avgScore'
        ));
    }

    /**
     * Show individual student detail page with all module performances (for instructors)
     */
    public function showStudent(Student $student)
    {
        // Load all performance data for this student
        $student->load(['modulePerformances.module', 'user']);
        
        // Get all modules for reference
        $allModules = Module::all();
        
        // Group performances by module
        $modulePerformances = $student->modulePerformances->keyBy('module_id');
        
        // Calculate overall stats
        $hasPerformance = $student->modulePerformances->isNotEmpty();
        $overallStats = [];
        $aggregatedXAI = [];
        
        if ($hasPerformance) {
            $performances = $student->modulePerformances;
            $overallStats = [
                'modules_completed' => $performances->count(),
                'total_modules' => $allModules->count(),
                'best_lms' => $performances->max('learning_mastery_score'),
                'avg_lms' => round($performances->avg('learning_mastery_score'), 1),
                'avg_score' => round($performances->avg('score_percentage'), 1),
                'avg_confidence' => round($performances->avg('avg_confidence'), 2),
                'avg_hint_usage' => round($performances->avg('hint_usage_percentage'), 1),
                'avg_time_per_question' => round($performances->avg('avg_time_per_question'), 1),
                'total_answer_changes' => round($performances->avg('answer_changes_rate'), 4),
                'avg_review_percentage' => round($performances->avg('review_percentage'), 1),
            ];
            
            // Determine overall mastery level from ML classification (best performance)
            $overallStats['mastery_level'] = $performances->sortByDesc('learning_mastery_score')->first()->mastery_level ?? 'developing';
            
            // Aggregate XAI data across all modules
            $aggregatedXAI = [
                'avg_lms' => $overallStats['avg_lms'],
                'avg_confidence' => round($performances->avg('ml_prediction_confidence') * 100, 1),
                'modules_count' => $performances->count(),
                'positive_factors' => $this->aggregateFactors($performances->pluck('top_positive_factors')),
                'negative_factors' => $this->aggregateFactors($performances->pluck('top_negative_factors')),
                'level_distribution' => $performances->groupBy('mastery_level')->map->count()->toArray(),
            ];
        }
        
        // Get warning history
        $warnings = \App\Models\StudentNotification::where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('dashboard.instructor.student-detail', compact(
            'student',
            'allModules',
            'modulePerformances',
            'hasPerformance',
            'overallStats',
            'aggregatedXAI',
            'warnings'
        ));
    }

    /**
     * Aggregate XAI factors across multiple modules, counting occurrences
     */
    private function aggregateFactors($factorStrings): array
    {
        $counts = [];
        foreach ($factorStrings as $str) {
            foreach (explode(', ', $str ?? '') as $factor) {
                $factor = trim($factor);
                if ($factor) {
                    $counts[$factor] = ($counts[$factor] ?? 0) + 1;
                }
            }
        }
        arsort($counts);
        return array_slice($counts, 0, 5, true);
    }

    /**
     * Show module detail page for students
     */
    public function showModule(Module $module)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        // Get performance for this module if exists
        $performance = null;
        if ($student) {
            $performance = StudentModulePerformance::where('student_id', $student->id)
                ->where('module_id', $module->id)
                ->first();
        }
        
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
        
        return view('dashboard.module-detail', compact('module', 'moduleData', 'performance', 'student'));
    }

    /**
     * Send a warning notification to a student
     */
    public function sendWarning(Request $request, Student $student)
    {
        $request->validate([
            'warning_type' => 'required|in:performance,attendance,engagement',
            'message' => 'required|string|max:1000',
        ]);
        
        $student->load('user');
        
        // Get the student's email
        $email = $student->user->email ?? null;
        
        if (!$email) {
            return back()->with('error', 'Student email not found.');
        }
        
        // Create notification in database
        \App\Models\StudentNotification::create([
            'student_id' => $student->id,
            'sender_id' => Auth::id(),
            'type' => $request->warning_type === 'performance' ? 'warning' : 'info',
            'title' => 'Academic Warning: ' . ucfirst($request->warning_type),
            'message' => $request->message,
        ]);
        
        // Send the warning email (using Laravel's Mail facade)
        try {
            \Illuminate\Support\Facades\Mail::raw(
                $request->message,
                function ($mail) use ($email, $student, $request) {
                    $mail->to($email)
                         ->subject("Academic Warning: {$request->warning_type}")
                         ->from(config('mail.from.address', 'noreply@meeple.test'), 'Meeple Learning System');
                }
            );
            
            return back()->with('success', "Warning notification sent to {$student->name} ({$email})");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
    /**
     * Generate comprehensive AI insights for a student
     */
    public function generateAIInsights(\Illuminate\Http\Request $request, Student $student, \App\Services\GeminiInsightsService $geminiService)
    {
        try {
            $insight = $geminiService->generateStudentInsights($student);
            
            return response()->json([
                'success' => true,
                'insight' => \Illuminate\Support\Str::markdown($insight)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show module settings page for instructors
     */
    public function showModuleSettings(Module $module)
    {
        // Get module statistics
        $stats = [
            'questions_count' => $module->questions()->count(),
            'students_completed' => StudentModulePerformance::where('module_id', $module->id)->count(),
            'avg_lms' => round(StudentModulePerformance::where('module_id', $module->id)->avg('learning_mastery_score') ?? 0, 1),
            'total_attempts' => \App\Models\LevelIndicatorAttempt::where('module_id', $module->id)->count(),
        ];
        
        // Load questions with answers for the question bank
        $questions = Question::where('module_id', $module->id)
            ->with('answers')
            ->orderBy('id')
            ->get();
        
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
        
        return view('dashboard.instructor.module-settings', compact('module', 'moduleData', 'stats', 'questions'));
    }

    /**
     * Update module settings (max attempts, etc.)
     */
    public function updateModuleSettings(Request $request, Module $module)
    {
        $validated = $request->validate([
            'max_level_indicator_attempts' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string|max:500',
        ]);
        
        $module->max_level_indicator_attempts = $validated['max_level_indicator_attempts'];
        
        if (isset($validated['description'])) {
            $module->description = $validated['description'];
        }
        
        $module->save();
        
        return back()->with('success', 'Module settings updated successfully!');
    }

    /**
     * Store a new question with answers for a module
     */
    public function storeQuestion(Request $request, Module $module)
    {
        $request->validate([
            'question_text' => 'required|string|max:2000',
            'type' => 'required|in:mcq,true_false,fill_in_blank',
            'difficulty' => 'required|integer|min:1|max:3',
            'is_hard' => 'sometimes|boolean',
            'answers' => 'required|array|min:1',
            'answers.*.answer_text' => 'required|string|max:1000',
            'answers.*.is_correct' => 'sometimes|boolean',
        ]);

        $question = Question::create([
            'question_text' => $request->question_text,
            'type' => $request->type,
            'difficulty' => $request->difficulty,
            'is_hard' => $request->difficulty >= 3 || $request->boolean('is_hard'),
            'module_id' => $module->id,
        ]);

        foreach ($request->answers as $answer) {
            $question->answers()->create([
                'answer_text' => $answer['answer_text'],
                'is_correct' => $answer['is_correct'] ?? false,
            ]);
        }

        return back()->with('success', 'Question added successfully!');
    }

    /**
     * Update an existing question and its answers
     */
    public function updateQuestion(Request $request, Module $module, Question $question)
    {
        if ($question->module_id !== $module->id) {
            abort(403, 'Question does not belong to this module.');
        }

        $request->validate([
            'question_text' => 'required|string|max:2000',
            'type' => 'required|in:mcq,true_false,fill_in_blank',
            'difficulty' => 'required|integer|min:1|max:3',
            'is_hard' => 'sometimes|boolean',
            'answers' => 'required|array|min:1',
            'answers.*.answer_text' => 'required|string|max:1000',
            'answers.*.is_correct' => 'sometimes|boolean',
        ]);

        $question->update([
            'question_text' => $request->question_text,
            'type' => $request->type,
            'difficulty' => $request->difficulty,
            'is_hard' => $request->difficulty >= 3 || $request->boolean('is_hard'),
        ]);

        // Replace all answers: delete existing, insert new
        $question->answers()->delete();
        foreach ($request->answers as $answer) {
            $question->answers()->create([
                'answer_text' => $answer['answer_text'],
                'is_correct' => $answer['is_correct'] ?? false,
            ]);
        }

        return back()->with('success', 'Question updated successfully!');
    }

    /**
     * Delete a question and its answers (cascade)
     */
    public function deleteQuestion(Module $module, Question $question)
    {
        if ($question->module_id !== $module->id) {
            abort(403, 'Question does not belong to this module.');
        }

        $question->delete(); // answers cascade via FK

        return back()->with('success', 'Question deleted successfully!');
    }

    /**
     * Bulk import questions from Excel/CSV file
     */
    public function importQuestions(Request $request, Module $module)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new \App\Imports\QuestionsImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('excel_file'));

            $count = 0;
            foreach ($import->questions as $q) {
                $difficulty = (int) ($q['difficulty'] ?? 2);
                $isHard = isset($q['is_hard'])
                    ? in_array(strtolower(trim($q['is_hard'])), ['true', '1', 'yes'], true)
                    : $difficulty >= 3;

                $question = Question::create([
                    'question_text' => $q['question_text'],
                    'type' => $q['type'],
                    'difficulty' => $difficulty,
                    'is_hard' => $isHard,
                    'module_id' => $module->id,
                ]);

                foreach ($q['answers'] as $answer) {
                    $question->answers()->create([
                        'answer_text' => $answer['answer_text'],
                        'is_correct' => $answer['is_correct'],
                    ]);
                }
                $count++;
            }

            return back()->with('success', "{$count} questions imported successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a CSV template for question bulk import
     */
    public function downloadQuestionTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="question_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, [
                'question_text', 'type', 'difficulty', 'is_hard',
                'answer_1', 'correct_1',
                'answer_2', 'correct_2',
                'answer_3', 'correct_3',
                'answer_4', 'correct_4',
            ]);
            // Example rows
            fputcsv($file, [
                'What is the capital of France?', 'mcq', '2', 'false',
                'Paris', 'true',
                'London', 'false',
                'Berlin', 'false',
                'Madrid', 'false',
            ]);
            fputcsv($file, [
                'The Earth is flat.', 'true_false', '1', 'false',
                'True', 'false',
                'False', 'true',
                '', '',
                '', '',
            ]);
            fputcsv($file, [
                'What is 2+2?', 'fill_in_blank', '1', 'false',
                '4', 'true',
                '', '',
                '', '',
                '', '',
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export student feature data as CSV for ML retraining.
     * Supports: ?module=all (default) or ?module={id} for per-module export.
     */
    public function exportData(Request $request)
    {
        $moduleFilter = $request->get('module', 'all');

        // Build query
        $query = StudentModulePerformance::with(['student', 'module']);

        if ($moduleFilter !== 'all') {
            $query->where('module_id', (int) $moduleFilter);
        }

        $performances = $query->orderBy('module_id')->orderBy('student_id')->get();

        // Determine filename
        if ($moduleFilter !== 'all' && $performances->isNotEmpty()) {
            $moduleName = str_replace(' ', '_', strtolower($performances->first()->module->name));
            $filename = "xscaffold_pipeline_data_{$moduleName}_" . now()->format('Y-m-d') . '.csv';
        } else {
            $filename = 'xscaffold_pipeline_data_all_modules_' . now()->format('Y-m-d') . '.csv';
        }

        // CSV headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $columns = [
            'student_id',
            'student_name',
            'module_name',
            // Tier 1: Core 6 Features
            'score_percentage',
            'hard_question_accuracy',
            'hint_usage_percentage',
            'avg_confidence',
            'answer_changes_rate',
            'tab_switches_rate',
            // Tier 2: 5 ML Predictor Features
            'avg_time_per_question',
            'review_percentage',
            'avg_first_action_latency',
            'clicks_per_question',
            'performance_trend',
            // ML Outputs
            'learning_mastery_score',
            'mastery_level',
            'ml_prediction_confidence',
            'prediction_source',
            'recorded_at',
        ];

        $callback = function () use ($performances, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($performances as $perf) {
                fputcsv($file, [
                    $perf->student->student_id ?? '',
                    $perf->student->name ?? '',
                    $perf->module->name ?? '',
                    $perf->score_percentage,
                    $perf->hard_question_accuracy,
                    $perf->hint_usage_percentage,
                    $perf->avg_confidence,
                    $perf->answer_changes_rate,
                    $perf->tab_switches_rate,
                    $perf->avg_time_per_question,
                    $perf->review_percentage,
                    $perf->avg_first_action_latency,
                    $perf->clicks_per_question,
                    $perf->performance_trend,
                    $perf->learning_mastery_score,
                    $perf->mastery_level,
                    $perf->ml_prediction_confidence,
                    $perf->prediction_source,
                    $perf->created_at?->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
