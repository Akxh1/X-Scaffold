<x-app-layout>
    {{-- Level Indicator Exam - Show Page --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-blue-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            {{-- Back Navigation --}}
            <a href="{{ route('student.module.show', $module) }}" class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-6 transition-colors">
                <i class="fas fa-arrow-left"></i>
                Back to Module
            </a>

            {{-- Header --}}
            <div class="bg-gradient-to-r {{ $moduleData['gradient'] }} rounded-3xl p-8 mb-8 relative overflow-hidden shadow-xl">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <i class="fas fa-clipboard-check text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Level Indicator Exam</h1>
                            <p class="text-white/80">{{ $moduleData['title'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attempt Status --}}
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 mb-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-blue-500 text-xl"></i>
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-white">Attempt Status</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $attemptCount }} of {{ $maxAttempts }} attempts used
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        @for($i = 1; $i <= $maxAttempts; $i++)
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                                {{ $i <= $attemptCount ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400' }}">
                                {{ $i }}
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Latest Result Summary (if exists) --}}
            @if($latestAttempt)
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 mb-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Latest Result (Attempt {{ $latestAttempt->attempt_number }})
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        @php $badge = $latestAttempt->getMasteryBadge(); @endphp
                        <p class="text-2xl">{{ $badge['icon'] }}</p>
                        <p class="text-xs {{ $badge['text'] }} font-semibold mt-1">{{ $badge['label'] }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ round($latestAttempt->score_percentage, 1) }}%</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Score</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold {{ $badge['text'] }}">{{ round($latestAttempt->learning_mastery_score, 1) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">LMS Score</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ round($latestAttempt->ml_prediction_confidence * 100) }}%</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Confidence</p>
                    </div>
                </div>
                
                <a href="{{ route('level-indicator.results', [$module, $latestAttempt]) }}" 
                   class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                    <i class="fas fa-chart-bar"></i>
                    View Full Results & SHAP Analysis
                </a>
            </div>
            @endif

            {{-- Start/Retry Button --}}
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 shadow-sm">
                @if($canAttempt)
                    <div class="text-center">
                        <div class="mb-6">
                            <i class="fas fa-brain text-6xl text-blue-500 mb-4"></i>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">
                                {{ $attemptCount > 0 ? 'Ready to Try Again?' : 'Ready to Begin?' }}
                            </h3>
                            <p class="text-slate-600 dark:text-slate-400 max-w-md mx-auto">
                                This diagnostic exam will assess your knowledge and generate a personalized Learning Mastery Score (LMS).
                                You have <strong>{{ $maxAttempts - $attemptCount }}</strong> attempt(s) remaining.
                            </p>
                        </div>
                        
                        <div class="space-y-3 text-left max-w-md mx-auto mb-6">
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-clock text-blue-500 w-5"></i>
                                <span>10 questions, approximately 15-20 minutes</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-chart-pie text-blue-500 w-5"></i>
                                <span>Tracks 11 behavioral features for accurate assessment</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-lightbulb text-blue-500 w-5"></i>
                                <span>AI-powered hints available (affects classification)</span>
                            </div>
                        </div>
                        
                        <a href="{{ route('level-indicator.start', $module) }}" 
                           class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl font-semibold text-lg shadow-lg shadow-blue-500/25 transition-all hover:shadow-blue-500/40 hover:scale-105">
                            <i class="fas fa-play"></i>
                            {{ $attemptCount > 0 ? 'Start New Attempt' : 'Start Exam' }}
                        </a>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-lock text-5xl text-slate-400 mb-4"></i>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Maximum Attempts Reached</h3>
                        <p class="text-slate-600 dark:text-slate-400 max-w-md mx-auto">
                            You have used all {{ $maxAttempts }} available attempts for this module. 
                            Contact your instructor if you need additional attempts.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Attempt History --}}
            @if($attempts->count() > 0)
            <div class="mt-6 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-slate-500"></i>
                    Attempt History
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Attempt</th>
                                <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Date</th>
                                <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Score</th>
                                <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">LMS</th>
                                <th class="text-left py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Level</th>
                                <th class="text-right py-3 px-2 font-medium text-slate-600 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempts as $attempt)
                            @php $aBadge = $attempt->getMasteryBadge(); @endphp
                            <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="py-3 px-2 text-slate-800 dark:text-white font-medium">#{{ $attempt->attempt_number }}</td>
                                <td class="py-3 px-2 text-slate-600 dark:text-slate-400">{{ $attempt->created_at->format('M j, Y g:i A') }}</td>
                                <td class="py-3 px-2 text-slate-800 dark:text-white">{{ round($attempt->score_percentage, 1) }}%</td>
                                <td class="py-3 px-2 {{ $aBadge['text'] }} font-semibold">{{ round($attempt->learning_mastery_score, 1) }}</td>
                                <td class="py-3 px-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $aBadge['bg'] }} {{ $aBadge['text'] }}">
                                        {{ $aBadge['icon'] }} {{ $aBadge['label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <a href="{{ route('level-indicator.results', [$module, $attempt]) }}" 
                                       class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline">
                                        <i class="fas fa-eye text-xs"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
