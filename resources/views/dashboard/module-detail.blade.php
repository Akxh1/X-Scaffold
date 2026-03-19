<x-app-layout>
    {{-- Module Detail Page --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            {{-- Back Navigation --}}
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-6 transition-colors">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>

            {{-- Module Header --}}
            <div class="bg-gradient-to-r {{ $moduleData['gradient'] }} rounded-3xl p-8 mb-8 relative overflow-hidden shadow-xl">
                {{-- Background decoration --}}
                <div class="absolute right-0 top-0 h-full w-1/2 opacity-10">
                    <i class="fas {{ $moduleData['icon'] }} text-white" style="font-size: 200px; position: absolute; right: -20px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white shadow-lg mb-4">
                                <i class="fas {{ $moduleData['icon'] }} text-2xl"></i>
                            </div>
                            <h1 class="text-3xl font-bold text-white mb-2">{{ $moduleData['title'] }}</h1>
                            <p class="text-white/80 max-w-xl">{{ $moduleData['description'] ?: 'Explore this module to enhance your knowledge and skills.' }}</p>
                        </div>
                        <div class="hidden md:block">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white/20 backdrop-blur-sm text-white">
                                <i class="fas fa-question-circle mr-2"></i>
                                {{ $moduleData['questions_count'] }} Questions
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Summary (if exists) --}}
            @if($performance)
            <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 mb-8 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-indigo-500"></i>
                    Your Performance Summary
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ round($performance->score_percentage, 1) }}%</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Score</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        @php
                            $lmsColors = [
                                'advanced' => 'text-emerald-600 dark:text-emerald-400',
                                'proficient' => 'text-blue-600 dark:text-blue-400',
                                'developing' => 'text-amber-600 dark:text-amber-400',
                                'at_risk' => 'text-red-600 dark:text-red-400',
                            ];
                            $levelBadges = [
                                'advanced' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-400', 'icon' => '🏆'],
                                'proficient' => ['bg' => 'bg-blue-100 dark:bg-blue-500/10', 'text' => 'text-blue-700 dark:text-blue-400', 'icon' => '📘'],
                                'developing' => ['bg' => 'bg-amber-100 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-400', 'icon' => '📙'],
                                'at_risk' => ['bg' => 'bg-red-100 dark:bg-red-500/10', 'text' => 'text-red-700 dark:text-red-400', 'icon' => '⚠️'],
                            ];
                            $badge = $levelBadges[$performance->mastery_level] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => '📊'];
                        @endphp
                        <p class="text-2xl">{{ $badge['icon'] }}</p>
                        <p class="text-xs {{ $badge['text'] }} font-semibold mt-1">{{ ucfirst(str_replace('_', ' ', $performance->mastery_level)) }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-lg font-bold {{ $lmsColors[$performance->mastery_level] ?? 'text-slate-800' }}">{{ round($performance->learning_mastery_score, 1) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">LMS Score</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ round($performance->avg_confidence, 1) }}/5</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Confidence</p>
                    </div>

                </div>
            </div>
            @endif

            {{-- Module Sections --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Level Indicator Exam Section --}}
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden shadow-sm hover:shadow-lg transition-all">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-clipboard-check text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Level Indicator Exam</h3>
                                <p class="text-white/80 text-sm">Assess your current knowledge level</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-6">
                            This diagnostic assessment measures your understanding across key topics. Your responses help us personalize your learning experience and track your mastery progress.
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-clock text-blue-500"></i>
                                <span>Approx. 15-20 minutes</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-list-ol text-blue-500"></i>
                                <span>{{ $moduleData['questions_count'] }} Questions</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-brain text-blue-500"></i>
                                <span>Generates your learning classification</span>
                            </div>
                        </div>

                        @if($performance)
                            @php
                                $latestAttempt = \App\Models\LevelIndicatorAttempt::getLatestAttempt($student->id, $module->id);
                                $attemptCount = \App\Models\LevelIndicatorAttempt::getAttemptCount($student->id, $module->id);
                                $canAttempt = \App\Models\LevelIndicatorAttempt::canAttempt($student->id, $module->id);
                                $maxAttempts = $module->max_level_indicator_attempts ?? 3;
                            @endphp
                            <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 rounded-xl mb-4">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                                <span class="text-sm text-green-700 dark:text-green-400 font-medium">
                                    Completed ({{ $attemptCount }}/{{ $maxAttempts }} attempts)
                                </span>
                                <span class="text-sm text-green-600 dark:text-green-300 ml-auto">{{ ucfirst(str_replace('_', ' ', $performance->mastery_level)) }}</span>
                            </div>
                            <div class="flex gap-3">
                                <a href="{{ route('level-indicator.show', $module) }}" 
                                   class="flex-1 py-3 bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 rounded-xl font-medium text-center hover:bg-blue-200 dark:hover:bg-blue-500/30 transition">
                                    <i class="fas fa-chart-bar mr-2"></i>View Results
                                </a>
                                @if($canAttempt)
                                <a href="{{ route('level-indicator.start', $module) }}" 
                                   class="flex-1 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl font-medium text-center shadow-lg shadow-blue-500/25 transition-all">
                                    <i class="fas fa-redo mr-2"></i>Retake
                                </a>
                                @endif
                            </div>
                        @else
                            <a href="{{ route('level-indicator.start', $module) }}" class="block w-full py-3 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl font-medium text-center shadow-lg shadow-blue-500/25 transition-all hover:shadow-blue-500/40">
                                <i class="fas fa-play mr-2"></i>Start Level Indicator
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Mock Exam Section --}}
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden shadow-sm hover:shadow-lg transition-all">
                    <div class="bg-gradient-to-r from-purple-500 to-violet-600 p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-magic text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Mock Exam</h3>
                                <p class="text-white/80 text-sm">Practice with AI-powered hints</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-6">
                            Practice your skills with our adaptive mock exam. Receive personalized AI-generated hints based on your learning profile when you need help. Perfect for exam preparation!
                        </p>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-lightbulb text-purple-500"></i>
                                <span>AI-powered adaptive hints</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-sync-alt text-purple-500"></i>
                                <span>Unlimited attempts</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-chart-bar text-purple-500"></i>
                                <span>Instant feedback & explanations</span>
                            </div>
                        </div>

                        <a href="{{ route('mock-exam.show', $module) }}" class="block w-full py-3 bg-gradient-to-r from-purple-500 to-violet-600 hover:from-purple-600 hover:to-violet-700 text-white rounded-xl font-medium text-center shadow-lg shadow-purple-500/25 transition-all hover:shadow-purple-500/40">
                            <i class="fas fa-rocket mr-2"></i>Start Mock Exam
                        </a>
                    </div>
                </div>
            </div>

            {{-- Additional Resources (Placeholder) --}}
            <div class="mt-8 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-book-reader text-indigo-500"></i>
                    Learning Resources
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 opacity-60">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-video text-blue-500"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-300">Video Lectures</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Coming soon</p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 opacity-60">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-file-pdf text-red-500"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-300">Study Materials</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Coming soon</p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 opacity-60">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-comments text-green-500"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-300">Discussion Forum</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
