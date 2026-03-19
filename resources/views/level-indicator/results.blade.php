<x-app-layout>
    {{-- Level Indicator Exam - Results Page with Full SHAP Breakdown --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-blue-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            {{-- Back Navigation --}}
            <a href="{{ route('level-indicator.show', $module) }}" 
               class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-6 transition-colors">
                <i class="fas fa-arrow-left"></i>
                Back to Exam Overview
            </a>

            {{-- Header --}}
            <div class="bg-gradient-to-r {{ $moduleData['gradient'] }} rounded-3xl p-8 mb-8 relative overflow-hidden shadow-xl">
                {{-- Info Icon --}}
                <button onclick="document.getElementById('classificationDisclaimer').classList.toggle('hidden')" 
                        class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-sm flex items-center justify-center text-white transition-all" 
                        title="About Classification">
                    <i class="fas fa-info text-sm"></i>
                </button>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                <span class="text-3xl">{{ $badge['icon'] }}</span>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">Exam Results</h1>
                                <p class="text-white/80">{{ $moduleData['title'] }} • Attempt {{ $attempt->attempt_number }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <div class="text-4xl md:text-5xl font-bold text-white mb-1">{{ $badge['label'] }}</div>
                        <div class="text-white/70 text-sm">ML Classification</div>
                        <div class="text-white/60 text-xs mt-1">LMS: {{ round($attempt->learning_mastery_score, 1) }}</div>
                    </div>
                </div>
            </div>

            {{-- Classification Disclaimer --}}
            <div id="classificationDisclaimer" class="hidden mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">About Your Classification</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
                            Your <strong>mastery classification</strong> ({{ $badge['label'] }}) is determined by an XGBoost ML model trained on teacher-labelled student data. It is the <strong>authoritative</strong> assessment of your learning level. The <strong>Learning Mastery Score (LMS)</strong> is a supplementary formulaic score derived from your behavioural metrics using a research-backed formula. The ML classification may differ from what the LMS range alone would suggest, as the model captures nuances that the formula cannot.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Result Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl p-5 shadow-sm border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="fas fa-check text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400">Score</span>
                    </div>
                    <div class="text-3xl font-bold text-slate-800 dark:text-white">{{ round($attempt->score_percentage, 1) }}%</div>
                </div>
                
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl p-5 shadow-sm border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl {{ $badge['bg'] }} flex items-center justify-center">
                            <span class="text-lg">{{ $badge['icon'] }}</span>
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400">Level</span>
                    </div>
                    <div class="text-2xl font-bold {{ $badge['text'] }}">{{ $badge['label'] }}</div>
                </div>
                
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl p-5 shadow-sm border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="fas fa-brain text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400">Confidence</span>
                    </div>
                    <div class="text-3xl font-bold text-slate-800 dark:text-white">{{ round($attempt->ml_prediction_confidence * 100) }}%</div>
                </div>
                
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl p-5 shadow-sm border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fas fa-fire text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400">Hard Q Acc</span>
                    </div>
                    <div class="text-3xl font-bold text-slate-800 dark:text-white">{{ round($attempt->hard_question_accuracy, 1) }}%</div>
                </div>
                
                {{-- ML Prediction Source --}}
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl p-5 shadow-sm border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-3 mb-2">
                        @if($attempt->prediction_source === 'flask_api')
                        <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="fas fa-server text-green-600 dark:text-green-400"></i>
                        </div>
                        @else
                        <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                            <i class="fas fa-calculator text-cyan-600 dark:text-cyan-400"></i>
                        </div>
                        @endif
                        <span class="text-sm text-slate-500 dark:text-slate-400">ML Source</span>
                    </div>
                    @if($attempt->prediction_source === 'flask_api')
                    <div class="text-sm font-bold text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i>Flask API
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Full ML model + PKL</p>
                    @else
                    <div class="text-sm font-bold text-cyan-600 dark:text-cyan-400">
                        <i class="fas fa-laptop-code mr-1"></i>Local Fallback
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">LMS Formula</p>
                    @endif
                </div>
            </div>

            {{-- XAI Explanation Summary --}}
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-robot text-blue-500"></i>
                    AI Analysis Summary
                </h3>
                <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                        {{ $attempt->xai_explanation ?: 'Standard performance patterns observed.' }}
                    </p>
                </div>
                
                @if($attempt->top_positive_factors || $attempt->top_negative_factors)
                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    @if($attempt->top_positive_factors)
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <h4 class="text-sm font-semibold text-green-800 dark:text-green-400 mb-2 flex items-center gap-2">
                            <i class="fas fa-arrow-trend-up"></i> Strengths
                        </h4>
                        <p class="text-green-700 dark:text-green-300 text-sm">{{ $attempt->top_positive_factors }}</p>
                    </div>
                    @endif
                    @if($attempt->top_negative_factors)
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
                        <h4 class="text-sm font-semibold text-red-800 dark:text-red-400 mb-2 flex items-center gap-2">
                            <i class="fas fa-arrow-trend-down"></i> Areas to Improve
                        </h4>
                        <p class="text-red-700 dark:text-red-300 text-sm">{{ $attempt->top_negative_factors }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Full SHAP Breakdown (11 Features) --}}
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-indigo-500"></i>
                    Full SHAP Feature Breakdown
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Contribution of each behavioral feature to your Learning Mastery Score. Positive values (+) help your score, negative values (-) reduce it.
                </p>
                
                <div class="space-y-3">
                    @forelse($shapValues as $feature => $data)
                    <div class="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/30 transition border border-slate-100 dark:border-slate-700">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-slate-800 dark:text-white">
                                    {{ ucwords(str_replace('_', ' ', $feature)) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $data['description'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="text-sm font-mono text-slate-600 dark:text-slate-300">{{ $data['value'] ?? '-' }}</span>
                            </div>
                            <div class="w-24 text-right">
                                @php
                                    $contribution = $data['contribution'] ?? 0;
                                    $isPositive = $contribution > 0;
                                @endphp
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-bold
                                    {{ $isPositive ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($contribution < 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400') }}">
                                    <i class="fas {{ $isPositive ? 'fa-arrow-up' : ($contribution < 0 ? 'fa-arrow-down' : 'fa-minus') }} text-xs"></i>
                                    {{ $isPositive ? '+' : '' }}{{ number_format($contribution, 3) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                        <i class="fas fa-info-circle mr-2"></i>
                        Detailed SHAP breakdown not available for this attempt.
                    </p>
                    @endforelse
                </div>
            </div>

            {{-- Raw Feature Values --}}
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-table text-slate-500"></i>
                    Behavioral Metrics Captured
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Score</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->score_percentage, 1) }}%</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Hard Q Accuracy</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->hard_question_accuracy, 1) }}%</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Hint Usage</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->hint_usage_percentage, 1) }}%</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Avg Confidence</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->avg_confidence, 1) }}/5</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Answer Changes</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->answer_changes_rate, 2) }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Tab Switches</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->tab_switches_rate, 2) }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Avg Time/Q</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->avg_time_per_question, 1) }}s</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Review %</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->review_percentage, 1) }}%</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">1st Action Latency</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->avg_first_action_latency, 1) }}s</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Clicks/Q</p>
                        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ round($attempt->clicks_per_question, 1) }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Perf Trend</p>
                        <p class="text-xl font-bold {{ $attempt->performance_trend >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $attempt->performance_trend >= 0 ? '+' : '' }}{{ round($attempt->performance_trend, 1) }}%
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mt-8">
                @if($canAttempt)
                <a href="{{ route('level-indicator.start', $module) }}" 
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-blue-500/30 transition-all hover:scale-105">
                    <i class="fas fa-redo"></i>
                    Try Again ({{ $maxAttempts - $attempts->count() }} left)
                </a>
                @endif
                <a href="{{ route('student.module.show', $module) }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    <i class="fas fa-arrow-left"></i>
                    Back to Module
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
