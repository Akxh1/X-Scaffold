<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentModulePerformance extends Model
{
    use HasFactory;

    protected $table = 'student_module_performance';

    protected $fillable = [
        'student_id', 
        'module_id',
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
        // ML Prediction Outputs (updated by Level Indicator Exam)
        'learning_mastery_score',  // ML predicted LMS (0-100)
        'mastery_level',           // at_risk, developing, proficient, advanced
        'ml_prediction_confidence', // Model confidence (0-1)
        // XAI Fields (SHAP explanations)
        'shap_values',
        'xai_explanation',
        'top_positive_factors',
        'top_negative_factors',
        'prediction_source',
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
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Calculate Learning Mastery Score using the research-backed formula.
     * LMS = 0.50×S + 0.15×Hd + 10×Ccal + 10×Ks + 10×Af − 15×Hu^1.5
     */
    public function calculateLMS(): float
    {
        $S = $this->score_percentage;
        $Hd = $this->hard_question_accuracy;
        $Hu = $this->hint_usage_percentage / 100; // Convert to 0-1 scale
        
        // Calibration bonus: how well confidence matches performance
        $Ccal = $this->calculateCalibrationBonus();
        
        // Knowledge stability: inverse of answer changes (lower changes = more stable)
        $Ks = $this->calculateStabilityBonus();
        
        // Attention focus: inverse of tab switches (fewer switches = more focused)
        $Af = $this->calculateAttentionBonus();
        
        $lms = (0.50 * $S) + (0.15 * $Hd) + (10 * $Ccal) + (10 * $Ks) + (10 * $Af) - (15 * pow($Hu, 1.5));
        
        // Clamp to 0-100 range
        return max(0, min(100, $lms));
    }

    /**
     * Calculate calibration bonus based on confidence-performance alignment.
     */
    private function calculateCalibrationBonus(): float
    {
        // Higher confidence with higher score = good calibration
        $expectedConfidence = $this->score_percentage / 20; // Scale 0-100 to 0-5
        $diff = abs($this->avg_confidence - $expectedConfidence);
        return max(0, 1 - ($diff / 5)); // 1.0 = perfect calibration, 0 = poor
    }

    /**
     * Calculate stability bonus based on answer changes rate.
     */
    private function calculateStabilityBonus(): float
    {
        // Lower answer changes = more stable knowledge
        return max(0, 1 - $this->answer_changes_rate);
    }

    /**
     * Calculate attention bonus based on tab switches rate.
     */
    private function calculateAttentionBonus(): float
    {
        // Lower tab switches = better attention
        return max(0, 1 - min(1, $this->tab_switches_rate / 2));
    }

    /**
     * Classify mastery level based on LMS score.
     */
    public function classifyMasteryLevel(): string
    {
        $lms = $this->learning_mastery_score;
        
        if ($lms >= 76) {
            return 'advanced';
        } elseif ($lms >= 56) {
            return 'proficient';
        } elseif ($lms >= 36) {
            return 'developing';
        } else {
            return 'at_risk';
        }
    }

    /**
     * Calculate LMS and update the record.
     */
    public function updateLMS(): self
    {
        $this->learning_mastery_score = $this->calculateLMS();
        $this->mastery_level = $this->classifyMasteryLevel();
        $this->save();
        
        return $this;
    }
}
