<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestExamController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\QuestionController;
use App\Http\Controllers\HintController;
use App\Http\Controllers\RiskPredictorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\LevelIndicatorExamController;
use App\Http\Controllers\MockExamController;
use App\Http\Controllers\NotificationController;

// ═══════════════════════════════════════════════════════════════════
// 1. PUBLIC ROUTES — No authentication required
// ═══════════════════════════════════════════════════════════════════
Route::get('/', function () {
    return view('welcome');
});

Route::get('/Test-Exam', [TestExamController::class, 'index'])->name('test-exam.index');
Route::post('/generate-hint', [HintController::class, 'generate']);
Route::get('/risk-predictor', [RiskPredictorController::class, 'index'])->name('risk.predictor');
Route::post('/risk-predictor/upload', [RiskPredictorController::class, 'upload'])->name('risk.predictor.upload');

// ═══════════════════════════════════════════════════════════════════
// 2. SHARED AUTH ROUTES — Any authenticated user
// ═══════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Student warnings (accessible by the student themselves)
    Route::get('/student/warnings', [StudentController::class, 'getWarnings']);
});

// Notification API — any authenticated user
Route::middleware('auth')->group(function () {
    Route::get('/api/notifications', [NotificationController::class, 'getMyNotifications']);
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('/api/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/api/notifications/send-warning', [NotificationController::class, 'sendWarning']);
    Route::get('/api/students/dropdown', [NotificationController::class, 'getStudentsForDropdown']);
});

// ═══════════════════════════════════════════════════════════════════
// 3. STUDENT ROUTES — role:student (admin can also access)
// ═══════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified', 'role:student'])->group(function () {

    // Student Dashboard
    Route::get('/dashboard/student', [DashboardController::class, 'studentDashboard'])
        ->name('student.dashboard');

    // Module Detail Page (student view)
    Route::get('/module/{module}', [DashboardController::class, 'showModule'])
        ->name('student.module.show');

    // Level Indicator Exam
    Route::get('/module/{module}/level-indicator', [LevelIndicatorExamController::class, 'show'])
        ->name('level-indicator.show');
    Route::get('/module/{module}/level-indicator/start', [LevelIndicatorExamController::class, 'start'])
        ->name('level-indicator.start');
    Route::post('/module/{module}/level-indicator/submit', [LevelIndicatorExamController::class, 'submit'])
        ->name('level-indicator.submit');
    Route::get('/module/{module}/level-indicator/results/{attempt}', [LevelIndicatorExamController::class, 'results'])
        ->name('level-indicator.results');

    // Mock Exam (Unlimited Practice with Adaptive Hints)
    Route::get('/module/{module}/mock-exam', [MockExamController::class, 'show'])
        ->name('mock-exam.show');
    Route::get('/module/{module}/mock-exam/start', [MockExamController::class, 'start'])
        ->name('mock-exam.start');
    Route::post('/module/{module}/mock-exam/submit', [MockExamController::class, 'submit'])
        ->name('mock-exam.submit');
    Route::get('/module/{module}/mock-exam/results/{attempt}', [MockExamController::class, 'results'])
        ->name('mock-exam.results');
});

// ═══════════════════════════════════════════════════════════════════
// 4. INSTRUCTOR ROUTES — role:teacher (admin can also access)
// ═══════════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified', 'role:teacher'])->group(function () {

    // Instructor Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Student Detail Page
    Route::get('/dashboard/student/{student}', [DashboardController::class, 'showStudent'])
        ->name('instructor.student.show');

    // Send Warning Notification
    Route::post('/dashboard/student/{student}/warn', [DashboardController::class, 'sendWarning'])
        ->name('instructor.student.warn');

    // Generate AI Insights
    Route::post('/dashboard/student/{student}/generate-insights', [DashboardController::class, 'generateAIInsights'])
        ->name('instructor.student.insights');

    // Module Settings
    Route::get('/dashboard/module/{module}/settings', [DashboardController::class, 'showModuleSettings'])
        ->name('instructor.module.settings');
    Route::post('/dashboard/module/{module}/settings', [DashboardController::class, 'updateModuleSettings'])
        ->name('instructor.module.settings.update');

    // Question Management (CRUD + Import)
    Route::post('/dashboard/module/{module}/questions', [DashboardController::class, 'storeQuestion'])
        ->name('instructor.module.questions.store');
    Route::put('/dashboard/module/{module}/questions/{question}', [DashboardController::class, 'updateQuestion'])
        ->name('instructor.module.questions.update');
    Route::delete('/dashboard/module/{module}/questions/{question}', [DashboardController::class, 'deleteQuestion'])
        ->name('instructor.module.questions.delete');
    Route::post('/dashboard/module/{module}/questions/import', [DashboardController::class, 'importQuestions'])
        ->name('instructor.module.questions.import');
    Route::get('/dashboard/module/questions/template', [DashboardController::class, 'downloadQuestionTemplate'])
        ->name('instructor.module.questions.template');

    // Export pipeline data as CSV (for ML retraining)
    Route::get('/dashboard/export-data', [DashboardController::class, 'exportData'])
        ->name('instructor.export.data');

    // Old teacher question upload routes
    Route::get('teacher/questions/upload', [QuestionController::class, 'showUploadForm'])->name('teacher.questions.upload');
    Route::post('teacher/questions/preview', [QuestionController::class, 'previewUpload'])->name('teacher.questions.preview');
    Route::post('teacher/questions/store', [QuestionController::class, 'storeUploaded'])->name('teacher.questions.store');
});


require __DIR__ . '/auth.php';
