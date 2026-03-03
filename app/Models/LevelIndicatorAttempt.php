<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Level Indicator Attempt Model
 * 
 * Tracks individual exam attempts for the Level Indicator Exam.
 * Stores all 11 behavioral features captured during the exam,
 * ML prediction results, and SHAP explanations.
 */
class LevelIndicatorAttempt extends Model
{
    use HasFactory;

    protected $table = 'level_indicator_attempts';

    protected $fillable = [
        'student_id',
        'module_id',
        'attempt_number',
        // Tier 1: Core 6 Features (LMS Components)
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
        // ML Prediction Outputs
        'learning_mastery_score',
        'mastery_level',
        'ml_prediction_confidence',
        // XAI Fields
        'shap_values',
        'xai_explanation',
        'top_positive_factors',
        'top_negative_factors',
        // Prediction metadata
        'prediction_source',
        // Raw data
        'question_ids',
        'answers_data',
    ];

    protected $casts = [
        'score_percentage' => 'decimal:2',
        'hard_question_accuracy' => 'decimal:2',
        'hint_usage_percentage' => 'decimal:2',
        'avg_confidence' => 'decimal:2',
        'answer_changes_rate' => 'decimal:4',
        'tab_switches_rate' => 'decimal:4',
        'avg_time_per_question' => 'decimal:2',
        'review_percentage' => 'decimal:2',
        'avg_first_action_latency' => 'decimal:2',
        'clicks_per_question' => 'decimal:2',
        'performance_trend' => 'decimal:2',
        'learning_mastery_score' => 'decimal:2',
        'ml_prediction_confidence' => 'decimal:4',
        'shap_values' => 'array',
        'question_ids' => 'array',
        'answers_data' => 'array',
    ];

    /**
     * Get the student that owns the attempt.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the module for this attempt.
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the latest attempt for a student and module.
     */
    public static function getLatestAttempt(int $studentId, int $moduleId): ?self
    {
        return self::where('student_id', $studentId)
            ->where('module_id', $moduleId)
            ->orderByDesc('attempt_number')
            ->first();
    }

    /**
     * Get the attempt count for a student and module.
     */
    public static function getAttemptCount(int $studentId, int $moduleId): int
    {
        return self::where('student_id', $studentId)
            ->where('module_id', $moduleId)
            ->count();
    }

    /**
     * Check if more attempts are allowed.
     */
    public static function canAttempt(int $studentId, int $moduleId): bool
    {
        $module = Module::find($moduleId);
        if (!$module) return false;
        
        $currentCount = self::getAttemptCount($studentId, $moduleId);
        return $currentCount < $module->max_level_indicator_attempts;
    }

    /**
     * Get the next attempt number.
     */
    public static function getNextAttemptNumber(int $studentId, int $moduleId): int
    {
        $latest = self::getLatestAttempt($studentId, $moduleId);
        return $latest ? $latest->attempt_number + 1 : 1;
    }

    /**
     * Get all attempts for a student and module.
     */
    public static function getAttemptHistory(int $studentId, int $moduleId)
    {
        return self::where('student_id', $studentId)
            ->where('module_id', $moduleId)
            ->orderByDesc('attempt_number')
            ->get();
    }

    /**
     * Get mastery level badge configuration.
     */
    public function getMasteryBadge(): array
    {
        $badges = [
            'advanced' => [
                'bg' => 'bg-emerald-100 dark:bg-emerald-500/10',
                'text' => 'text-emerald-700 dark:text-emerald-400',
                'icon' => '🏆',
                'label' => 'Advanced',
            ],
            'proficient' => [
                'bg' => 'bg-blue-100 dark:bg-blue-500/10',
                'text' => 'text-blue-700 dark:text-blue-400',
                'icon' => '📘',
                'label' => 'Proficient',
            ],
            'developing' => [
                'bg' => 'bg-amber-100 dark:bg-amber-500/10',
                'text' => 'text-amber-700 dark:text-amber-400',
                'icon' => '📙',
                'label' => 'Developing',
            ],
            'at_risk' => [
                'bg' => 'bg-red-100 dark:bg-red-500/10',
                'text' => 'text-red-700 dark:text-red-400',
                'icon' => '⚠️',
                'label' => 'At Risk',
            ],
        ];

        return $badges[$this->mastery_level] ?? $badges['developing'];
    }

    /**
     * Convert 11 features to array format for ML service.
     */
    public function toFeaturesArray(): array
    {
        return [
            'score_percentage' => (float) $this->score_percentage,
            'hard_question_accuracy' => (float) $this->hard_question_accuracy,
            'hint_usage_percentage' => (float) $this->hint_usage_percentage,
            'avg_confidence' => (float) $this->avg_confidence,
            'answer_changes_rate' => (float) $this->answer_changes_rate,
            'tab_switches_rate' => (float) $this->tab_switches_rate,
            'avg_time_per_question' => (float) $this->avg_time_per_question,
            'review_percentage' => (float) $this->review_percentage,
            'avg_first_action_latency' => (float) $this->avg_first_action_latency,
            'clicks_per_question' => (float) $this->clicks_per_question,
            'performance_trend' => (float) $this->performance_trend,
        ];
    }
}
