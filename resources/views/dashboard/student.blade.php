<x-app-layout>
    {{-- Premium Student Dashboard --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            {{-- Header Section --}}
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                            <span class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/25">
                                <i class="fas fa-graduation-cap text-xl text-white"></i>
                            </span>
                            My Learning Dashboard
                        </h1>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Track your progress across all modules and assessments</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="document.getElementById('dashboardDisclaimer').classList.toggle('hidden')" 
                                class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center justify-center text-slate-500 dark:text-slate-400 transition-all" 
                                title="About Classifications">
                            <i class="fas fa-info text-xs"></i>
                        </button>
                        @if($student)
                        <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $student->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                {{-- Classification Disclaimer --}}
                <div id="dashboardDisclaimer" class="hidden mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
                            Your <strong>mastery classification</strong> is determined by an XGBoost ML model trained on teacher-labelled student data and is the <strong>authoritative</strong> assessment. The <strong>LMS (Learning Mastery Score)</strong> is a supplementary formulaic score. The classification may differ from what the LMS range would suggest, as the ML model captures nuances the formula cannot.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Stats Overview --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                {{-- Total Modules --}}
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-500/10 rounded-xl">
                            <i class="fas fa-book-open text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $totalModules }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total Modules</p>
                </div>

                {{-- Completed Assessments --}}
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-green-100 dark:bg-green-500/10 rounded-xl">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $completedModules }}/{{ $totalModules }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Assessments Done</p>
                </div>

                {{-- Overall Classification --}}
                @php
                    $overallClassification = null;
                    if ($completedModules > 0) {
                        // Find dominant classification
                        $levels = $modules->where('has_performance', true)->pluck('mastery_level')->countBy();
                        $overallClassification = $levels->keys()->first();
                    }
                    $classColors = [
                        'advanced' => 'text-emerald-600 dark:text-emerald-400',
                        'proficient' => 'text-blue-600 dark:text-blue-400',
                        'developing' => 'text-amber-600 dark:text-amber-400',
                        'at_risk' => 'text-red-600 dark:text-red-400',
                    ];
                @endphp
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-500/10 rounded-xl">
                            <i class="fas fa-brain text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                    <p class="text-xl font-bold {{ $classColors[$overallClassification] ?? 'text-slate-800 dark:text-white' }}">{{ $overallClassification ? ucfirst(str_replace('_', ' ', $overallClassification)) : '—' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Overall Level</p>
                </div>

                {{-- Average Score --}}
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-500/10 rounded-xl">
                            <i class="fas fa-chart-line text-amber-600 dark:text-amber-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $avgScore ? $avgScore . '%' : '—' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Average Score</p>
                </div>
            </div>

            {{-- Modules Section Header --}}
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-layer-group text-indigo-500"></i>
                    Your Modules
                </h2>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $modules->count() }} modules available</span>
            </div>

            {{-- Modules Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($modules as $module)
                <a href="{{ route('student.module.show', $module['id']) }}" 
                   class="group bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    
                    {{-- Module Header with Gradient --}}
                    <div class="h-24 bg-gradient-to-r {{ $module['gradient'] }} p-5 relative overflow-hidden">
                        {{-- Icon --}}
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-20 text-white">
                            <i class="fas {{ $module['icon'] }} text-6xl"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-white shadow-lg">
                                <i class="fas {{ $module['icon'] }} text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Module Content --}}
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-1">
                            {{ $module['title'] }}
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 min-h-[40px]">
                            {{ $module['description'] ?: 'Explore this module to learn more.' }}
                        </p>
                        
                        {{-- Stats Row --}}
                        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                <i class="fas fa-question-circle mr-1"></i>
                                {{ $module['questions_count'] }} Questions
                            </span>
                            
                            @if($module['has_performance'])
                                @php
                                    $levelColors = [
                                        'advanced' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                                        'proficient' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
                                        'developing' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                        'at_risk' => 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400',
                                    ];
                                    $levelClass = $levelColors[$module['mastery_level']] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium {{ $levelClass }}">
                                    <i class="fas fa-trophy mr-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $module['mastery_level'])) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    Not Started
                                </span>
                            @endif
                        </div>
                        
                        {{-- Action Arrow --}}
                        <div class="flex justify-end mt-4">
                            <span class="text-indigo-600 dark:text-indigo-400 text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                                Enter Module <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- Empty State --}}
            @if($modules->count() === 0)
            <div class="text-center py-16 bg-white dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700">
                <i class="fas fa-folder-open text-5xl text-slate-300 dark:text-slate-600 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-700 dark:text-slate-300">No Modules Available</h3>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Check back later for new modules to explore.</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>