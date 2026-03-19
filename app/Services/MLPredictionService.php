<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\StudentModulePerformance;

/**
 * ML Prediction Service
 * 
 * Handles communication with the X-Scaffold ML API for:
 * - Mastery level prediction
 * - SHAP explanation retrieval
 * 
 * API Endpoint: http://localhost:5000 (Flask)
 */
class MLPredictionService
{
    /**
     * ML API Base URL (configure in .env as ML_API_URL)
     */
    protected string $apiUrl;

    /**
     * Timeout for API requests in seconds
     */
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.ml_api.url', 'http://localhost:5000');
        $this->timeout = config('services.ml_api.timeout', 10);
    }

    /**
     * Check if the ML API is available.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->apiUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('ML API health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get prediction for a StudentModulePerformance record.
     * 
     * @param StudentModulePerformance $performance
     * @return array|null Prediction result with SHAP explanation
     */
    public function predict(StudentModulePerformance $performance): ?array
    {
        $features = $this->extractFeatures($performance);
        
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->apiUrl}/predict", $features);
            
            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('ML Prediction successful', [
                    'student_id' => $performance->student_id,
                    'predicted_level' => $result['prediction']['mastery_level_name'] ?? 'unknown'
                ]);
                
                return $result;
            }
            
            Log::error('ML API prediction failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('ML API request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get prediction and update the performance record.
     * 
     * Called after Level Indicator Exam completion.
     * Updates learning_mastery_score with ML prediction and stores XAI data.
     * Supports multiple retakes - same row is updated.
     * 
     * @param StudentModulePerformance $performance
     * @return StudentModulePerformance Updated record
     */
    public function predictAndUpdate(StudentModulePerformance $performance): StudentModulePerformance
    {
        $result = $this->predict($performance);
        
        if ($result) {
            // Get ML classification (authoritative — trained on teacher labels)
            $mlLevel = $result['prediction']['mastery_level'] ?? 1;
            $confidence = $result['prediction']['confidence'] ?? 0.5;
            
            // Calculate real LMS using the research-backed formula (supplementary score)
            $features = [
                (float) ($performance->score_percentage ?? 50),
                (float) ($performance->hard_question_accuracy ?? 50),
                (float) ($performance->hint_usage_percentage ?? 25),
                (float) ($performance->avg_confidence ?? 3.0),
                (float) ($performance->answer_changes_rate ?? 0.5),
                (float) ($performance->tab_switches_rate ?? 1.0),
                (float) ($performance->avg_time_per_question ?? 60),
                (float) ($performance->review_percentage ?? 30),
                (float) ($performance->avg_first_action_latency ?? 5.0),
                (float) ($performance->clicks_per_question ?? 5.0),
                (float) ($performance->performance_trend ?? 0),
            ];
            $performance->learning_mastery_score = $this->predictLMSLocally($features);
            
            // Set mastery level name
            $levelNames = ['at_risk', 'developing', 'proficient', 'advanced'];
            $performance->mastery_level = $levelNames[$mlLevel] ?? 'developing';
            
            // Store confidence for reference
            $performance->ml_prediction_confidence = $confidence;
            
            // Store SHAP values if available
            if (isset($result['explanation']['contributions'])) {
                $performance->shap_values = $result['explanation']['contributions'];
            }
            
            // Store natural language explanation for LLM prompts
            if (isset($result['explanation']['natural_language'])) {
                $performance->xai_explanation = $result['explanation']['natural_language'];
            }
            
            // Store top factors for quick display
            if (isset($result['explanation']['top_positive'])) {
                $performance->top_positive_factors = implode(', ', $result['explanation']['top_positive']);
            }
            if (isset($result['explanation']['top_negative'])) {
                $performance->top_negative_factors = implode(', ', $result['explanation']['top_negative']);
            }
            
            $performance->save();
            
            Log::info('ML prediction saved to performance record', [
                'student_id' => $performance->student_id,
                'module_id' => $performance->module_id,
                'lms' => $performance->learning_mastery_score,
                'level' => $performance->mastery_level
            ]);
        }
        
        return $performance;
    }

    /**
     * Get batch predictions for multiple students.
     * 
     * @param array $performances Array of StudentModulePerformance records
     * @return array Results indexed by performance ID
     */
    public function batchPredict(array $performances): array
    {
        $students = [];
        foreach ($performances as $performance) {
            $features = $this->extractFeatures($performance);
            $features['student_id'] = $performance->student_id;
            $students[] = $features;
        }
        
        try {
            $response = Http::timeout($this->timeout * 2)
                ->post("{$this->apiUrl}/batch_predict", ['students' => $students]);
            
            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('ML API batch prediction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract 11 ML features from a performance record.
     */
    protected function extractFeatures(StudentModulePerformance $performance): array
    {
        return [
            'score_percentage' => (float) $performance->score_percentage,
            'hard_question_accuracy' => (float) $performance->hard_question_accuracy,
            'hint_usage_percentage' => (float) $performance->hint_usage_percentage,
            'avg_confidence' => (float) $performance->avg_confidence,
            'answer_changes_rate' => (float) $performance->answer_changes_rate,
            'tab_switches_rate' => (float) $performance->tab_switches_rate,
            'avg_time_per_question' => (float) $performance->avg_time_per_question,
            'review_percentage' => (float) $performance->review_percentage,
            'avg_first_action_latency' => (float) $performance->avg_first_action_latency,
            'clicks_per_question' => (float) $performance->clicks_per_question,
            'performance_trend' => (float) $performance->performance_trend,
        ];
    }

    /**
     * Format SHAP values into natural language for LLM prompts.
     * 
     * @param array $shapValues SHAP contribution values
     * @return string Natural language explanation
     */
    public function formatShapExplanation(array $shapValues): string
    {
        $positiveFactors = [];
        $negativeFactors = [];
        
        foreach ($shapValues as $feature => $data) {
            $value = $data['value'] ?? 0;
            $formattedFeature = str_replace('_', ' ', $feature);
            
            if ($value > 0.05) {
                $positiveFactors[] = "{$formattedFeature} (+" . number_format($value, 3) . ")";
            } elseif ($value < -0.05) {
                $negativeFactors[] = "{$formattedFeature} (" . number_format($value, 3) . ")";
            }
        }
        
        $explanation = "Based on SHAP analysis: ";
        
        if (!empty($positiveFactors)) {
            $explanation .= "Positive factors include " . implode(', ', array_slice($positiveFactors, 0, 3)) . ". ";
        }
        
        if (!empty($negativeFactors)) {
            $explanation .= "Areas needing improvement: " . implode(', ', array_slice($negativeFactors, 0, 3)) . ".";
        }
        
        return $explanation;
    }

    /**
     * Map ML mastery level (0-3) to hint level (1-3).
     * 
     * Used for HintController to determine scaffolding intensity.
     * 
     * @param int $masteryLevel 0=at_risk, 1=developing, 2=proficient, 3=advanced
     * @return int Hint level 1=proficient, 2=developing, 3=struggling
     */
    public function masteryToHintLevel(int $masteryLevel): int
    {
        // Inverse mapping: lower mastery = higher scaffolding
        return match($masteryLevel) {
            3 => 1, // advanced → minimal hints (L1)
            2 => 1, // proficient → minimal hints (L1)
            1 => 2, // developing → moderate hints (L2)
            0 => 3, // at_risk → intensive hints (L3)
            default => 2
        };
    }

    // ========================================================================
    // LOCAL FALLBACK PREDICTION (No Flask API Required)
    // Uses the trained LMS formula when Flask API is unavailable
    // ========================================================================

    /**
     * Predict and update with automatic fallback to local calculation.
     * 
     * Tries Flask API first, falls back to local LMS formula if unavailable.
     * Sets prediction_source to 'flask_api' or 'local_fallback'.
     * 
     * @param StudentModulePerformance $performance
     * @return StudentModulePerformance Updated record
     */
    public function predictWithFallback(StudentModulePerformance $performance): StudentModulePerformance
    {
        // Try Flask API first
        if ($this->isAvailable()) {
            Log::info('Using Flask ML API for prediction');
            $performance = $this->predictAndUpdate($performance);
            $performance->prediction_source = 'flask_api';
            $performance->save();
            return $performance;
        }
        
        Log::info('Flask ML API unavailable, using local fallback');
        
        // Extract features as array
        $features = [
            (float) ($performance->score_percentage ?? 50),
            (float) ($performance->hard_question_accuracy ?? 50),
            (float) ($performance->hint_usage_percentage ?? 25),
            (float) ($performance->avg_confidence ?? 3.0),
            (float) ($performance->answer_changes_rate ?? 0.5),
            (float) ($performance->tab_switches_rate ?? 1.0),
            (float) ($performance->avg_time_per_question ?? 60),
            (float) ($performance->review_percentage ?? 30),
            (float) ($performance->avg_first_action_latency ?? 5.0),
            (float) ($performance->clicks_per_question ?? 5.0),
            (float) ($performance->performance_trend ?? 0),
        ];
        
        // Calculate using local methods
        $lms = $this->predictLMSLocally($features);
        $level = $this->classifyLevel($lms);
        $confidence = $this->estimateConfidence($features);
        $shap = $this->generateShapAnalysisLocally($features, $performance);
        
        // Update record
        $performance->learning_mastery_score = $lms;
        $performance->mastery_level = $level;
        $performance->ml_prediction_confidence = $confidence;
        $performance->xai_explanation = $shap['explanation'];
        $performance->top_positive_factors = $shap['positive'];
        $performance->top_negative_factors = $shap['negative'];
        $performance->shap_values = $shap['values'];
        $performance->prediction_source = 'local_fallback';
        $performance->save();
        
        Log::info('Local ML prediction saved', [
            'student_id' => $performance->student_id,
            'module_id' => $performance->module_id,
            'lms' => $lms,
            'level' => $level,
            'prediction_source' => 'local_fallback'
        ]);
        
        return $performance;
    }

    /**
     * Predict LMS using the research-backed formula.
     * LMS = 0.50×S + 0.15×Hd + 10×Ccal + 10×Ks + 10×Af − 15×Hu^1.5
     */
    protected function predictLMSLocally(array $features): float
    {
        [$score, $hardAcc, $hints, $conf, $changes, $switches, 
         $time, $review, $latency, $clicks, $trend] = $features;
        
        $S = $score;
        $Hd = $hardAcc;
        $Hu = $hints / 100;
        
        // Calibration: how well confidence matches performance
        $expectedConf = $score / 20; // 0-5 scale
        $Ccal = abs($conf - $expectedConf) <= 1 ? 1 : 0;
        
        // Knowledge stability (low changes = stable)
        $Ks = max(0, 1 - ($changes - 0.5) / 1.0);
        $Ks = min(1, $Ks);
        
        // Attention factor (low switches = focused)
        $Af = max(0, 1 - ($switches - 1) / 2.0);
        $Af = min(1, $Af);
        
        $lms = (0.50 * $S) + (0.15 * $Hd) + (10 * $Ccal) + (10 * $Ks) + (10 * $Af) - (15 * pow($Hu, 1.5));
        
        return round(max(0, min(100, $lms)), 2);
    }

    /**
     * Classify mastery level from LMS score.
     */
    protected function classifyLevel(float $lms): string
    {
        if ($lms >= 76) return 'advanced';
        if ($lms >= 56) return 'proficient';
        if ($lms >= 36) return 'developing';
        return 'at_risk';
    }

    /**
     * Estimate prediction confidence based on feature consistency.
     */
    protected function estimateConfidence(array $features): float
    {
        [$score, $hardAcc, $hints, $conf, $changes, $switches, 
         $time, $review, $latency, $clicks, $trend] = $features;
        
        // Higher confidence when features are consistent
        $scoreConfMatch = 1 - abs(($score / 20) - $conf) / 5;
        $lowVariability = 1 - min(1, $changes);
        $goodFocus = 1 - min(1, $switches / 3);
        
        $confidence = ($scoreConfMatch + $lowVariability + $goodFocus) / 3;
        return round(max(0.5, min(0.98, $confidence)), 4);
    }

    /**
     * Generate full SHAP-style analysis of feature contributions.
     * Returns structured data for both summary and detailed display.
     */
    protected function generateShapAnalysisLocally(array $features, $perf): array
    {
        [$score, $hardAcc, $hints, $conf, $changes, $switches, 
         $time, $review, $latency, $clicks, $trend] = $features;
        
        $positive = [];
        $negative = [];
        $explanations = [];
        $shapValues = [];
        
        // Feature descriptions for display
        $descriptions = [
            'score_percentage' => 'Percentage of correct answers',
            'hard_question_accuracy' => 'Accuracy on difficult questions',
            'hint_usage_percentage' => 'Percentage of questions where hints were used',
            'avg_confidence' => 'Mean self-reported confidence (1-5)',
            'answer_changes_rate' => 'Average answer changes per question',
            'tab_switches_rate' => 'Average tab switches per question',
            'avg_time_per_question' => 'Mean time spent per question (seconds)',
            'review_percentage' => 'Percentage of questions marked for review',
            'avg_first_action_latency' => 'Mean time to first interaction (seconds)',
            'clicks_per_question' => 'Average clicks per question',
            'performance_trend' => 'Accuracy change (2nd half - 1st half)',
        ];
        
        // Calculate SHAP-style contributions for each feature
        
        // Score Percentage
        $scoreContrib = ($score - 50) * 0.01; // Normalized contribution
        $shapValues['score_percentage'] = [
            'value' => round($score, 1) . '%',
            'contribution' => round($scoreContrib, 3),
            'description' => $descriptions['score_percentage'],
        ];
        if ($score >= 70) {
            $positive[] = 'score_percentage';
            $explanations[] = "Strong score of {$score}% (+)";
        } elseif ($score < 50) {
            $negative[] = 'score_percentage';
            $explanations[] = "Score of {$score}% needs improvement (-)";
        }
        
        // Hard Question Accuracy
        $hardContrib = ($hardAcc - 50) * 0.005;
        $shapValues['hard_question_accuracy'] = [
            'value' => round($hardAcc, 1) . '%',
            'contribution' => round($hardContrib, 3),
            'description' => $descriptions['hard_question_accuracy'],
        ];
        if ($hardAcc >= 60) {
            $positive[] = 'hard_question_accuracy';
        } elseif ($hardAcc < 40) {
            $negative[] = 'hard_question_accuracy';
        }
        
        // Hint Usage
        $hintContrib = (25 - $hints) * 0.006; // Lower is better
        $shapValues['hint_usage_percentage'] = [
            'value' => round($hints, 1) . '%',
            'contribution' => round($hintContrib, 3),
            'description' => $descriptions['hint_usage_percentage'],
        ];
        if ($hints <= 20) {
            $positive[] = 'hint_usage_percentage';
            $explanations[] = "Low hint dependency ({$hints}%) (+)";
        } elseif ($hints >= 50) {
            $negative[] = 'hint_usage_percentage';
            $explanations[] = "High hint usage ({$hints}%) suggests scaffolding dependency (-)";
        }
        
        // Confidence
        $expectedConf = $score / 20;
        $calibrationError = abs($conf - $expectedConf);
        $confContrib = $calibrationError <= 1 ? 0.05 : -0.05;
        $shapValues['avg_confidence'] = [
            'value' => round($conf, 1) . '/5',
            'contribution' => round($confContrib, 3),
            'description' => $descriptions['avg_confidence'],
        ];
        if ($conf >= 3.5 && $score >= 60) {
            $positive[] = 'avg_confidence';
        } elseif ($conf >= 4 && $score < 50) {
            $negative[] = 'avg_confidence';
            $explanations[] = "Overconfidence detected (-)";
        }
        
        // Answer Changes Rate
        $changesContrib = (0.5 - $changes) * 0.1;
        $shapValues['answer_changes_rate'] = [
            'value' => round($changes, 2),
            'contribution' => round($changesContrib, 3),
            'description' => $descriptions['answer_changes_rate'],
        ];
        if ($changes <= 0.3) {
            $positive[] = 'answer_changes_rate';
        } elseif ($changes >= 1.0) {
            $negative[] = 'answer_changes_rate';
        }
        
        // Tab Switches Rate
        $switchesContrib = (1.0 - $switches) * 0.05;
        $shapValues['tab_switches_rate'] = [
            'value' => round($switches, 2),
            'contribution' => round($switchesContrib, 3),
            'description' => $descriptions['tab_switches_rate'],
        ];
        if ($switches <= 1.0) {
            $positive[] = 'tab_switches_rate';
        } elseif ($switches >= 2.5) {
            $negative[] = 'tab_switches_rate';
            $explanations[] = "Focus patterns need attention (-)";
        }
        
        // Avg Time Per Question
        $timeContrib = ($time >= 30 && $time <= 120) ? 0.02 : -0.02;
        $shapValues['avg_time_per_question'] = [
            'value' => round($time, 1) . 's',
            'contribution' => round($timeContrib, 3),
            'description' => $descriptions['avg_time_per_question'],
        ];
        
        // Review Percentage
        $reviewContrib = ($review >= 10 && $review <= 40) ? 0.02 : 0;
        $shapValues['review_percentage'] = [
            'value' => round($review, 1) . '%',
            'contribution' => round($reviewContrib, 3),
            'description' => $descriptions['review_percentage'],
        ];
        
        // First Action Latency
        $latencyContrib = ($latency >= 1 && $latency <= 5) ? 0.02 : -0.01;
        $shapValues['avg_first_action_latency'] = [
            'value' => round($latency, 1) . 's',
            'contribution' => round($latencyContrib, 3),
            'description' => $descriptions['avg_first_action_latency'],
        ];
        
        // Clicks Per Question
        $clicksContrib = ($clicks >= 2 && $clicks <= 8) ? 0.01 : 0;
        $shapValues['clicks_per_question'] = [
            'value' => round($clicks, 1),
            'contribution' => round($clicksContrib, 3),
            'description' => $descriptions['clicks_per_question'],
        ];
        
        // Performance Trend
        $trendContrib = $trend * 0.003;
        $shapValues['performance_trend'] = [
            'value' => ($trend >= 0 ? '+' : '') . round($trend, 1) . '%',
            'contribution' => round($trendContrib, 3),
            'description' => $descriptions['performance_trend'],
        ];
        if ($trend >= 5) {
            $positive[] = 'performance_trend';
            $explanations[] = "Positive improvement trend (+)";
        } elseif ($trend <= -10) {
            $negative[] = 'performance_trend';
            $explanations[] = "Performance declined during exam (-)";
        }
        
        $explanation = "Based on SHAP analysis: " . 
            (count($explanations) > 0 ? implode('. ', array_slice($explanations, 0, 4)) . '.' : 'Standard performance patterns observed.');
        
        return [
            'explanation' => $explanation,
            'positive' => implode(', ', array_slice($positive, 0, 5)),
            'negative' => implode(', ', array_slice($negative, 0, 5)),
            'values' => $shapValues,
        ];
    }
}
