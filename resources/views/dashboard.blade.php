<x-app-layout>
    @php
        $gradientMap = [
            1 => 'from-red-500 to-rose-600',
            2 => 'from-blue-500 to-cyan-600',
            3 => 'from-green-500 to-emerald-600',
            4 => 'from-purple-500 to-violet-600',
            5 => 'from-amber-500 to-orange-600',
            6 => 'from-indigo-500 to-blue-600',
        ];
    @endphp

    <style>
        #exportDropdown.open .export-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        #exportDropdown.open #exportChevron {
            transform: rotate(180deg);
        }
    </style>

    {{-- Multi-Module Instructor Dashboard with Light/Dark Mode --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            {{-- Header Section --}}
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                            <span class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/25">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </span>
                            Instructor Dashboard
                        </h1>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Multi-module student performance and learning mastery insights</p>
                    </div>
                    <div class="flex gap-3">
                        {{-- Export Data Dropdown --}}
                        <div class="relative" id="exportDropdown">
                            <button onclick="document.getElementById('exportDropdown').classList.toggle('open')"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-lg shadow-indigo-500/25">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export Data
                                <svg class="w-4 h-4 transition-transform duration-200" id="exportChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            {{-- Dropdown Menu --}}
                            <div class="export-menu absolute right-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 opacity-0 invisible translate-y-1 transition-all duration-200">
                                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-600">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Download Pipeline Data (CSV)</p>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('instructor.export.data') }}" 
                                       class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors group">
                                        <div class="p-1.5 bg-indigo-100 dark:bg-indigo-500/20 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-500/30 transition-colors">
                                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-white">All Modules (Combined)</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $totalPerformanceRecords }} records &middot; Best for ML retraining</p>
                                        </div>
                                    </a>
                                    @if($totalPerformanceRecords > 0)
                                    <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                                    <div class="px-4 py-1.5">
                                        <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Per Module</p>
                                    </div>
                                    @foreach($moduleStats as $module)
                                    @if($module['student_count'] > 0)
                                    <a href="{{ route('instructor.export.data', ['module' => $module['id']]) }}" 
                                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <div class="w-2 h-2 rounded-full bg-gradient-to-r {{ $gradientMap[$module['id']] ?? 'from-gray-500 to-slate-600' }}"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ $module['name'] }}</p>
                                        </div>
                                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $module['student_count'] }} records</span>
                                    </a>
                                    @endif
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Overview Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                {{-- Total Students --}}
                <div class="group bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 hover:border-blue-300 dark:hover:border-indigo-500/50 transition-all duration-300 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/10 rounded-xl">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $totalStudents }}</p>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Total Enrolled</p>
                    <p class="text-[10px] leading-tight text-slate-400 dark:text-slate-500 mt-2">Total number of unique students registered in the system across all modules.</p>
                </div>

                {{-- Exam Taken --}}
                <div class="group bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 hover:border-green-300 dark:hover:border-green-500/50 transition-all duration-300 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-green-100 dark:bg-green-500/10 rounded-xl">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $examTakenCount }}</p>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Assessed Students</p>
                    <p class="text-[10px] leading-tight text-slate-400 dark:text-slate-500 mt-2">Students who have completed at least one "Level Indicator Exam" in any module.</p>
                </div>

                {{-- Pending Exam --}}
                <div class="group bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 hover:border-amber-300 dark:hover:border-amber-500/50 transition-all duration-300 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-8 h-8 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-500/10 rounded-xl">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $examPendingCount }}</p>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Awaiting Assessment</p>
                    <p class="text-[10px] leading-tight text-slate-400 dark:text-slate-500 mt-2">Students who are enrolled but haven't taken any diagnostic exams yet.</p>
                </div>

                {{-- Total Performance Records --}}
                <div class="group bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 hover:border-indigo-300 dark:hover:border-indigo-500/50 transition-all duration-300 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-500/10 rounded-xl">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $totalPerformanceRecords }}</p>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Total Exam Attempt Data</p>
                    <p class="text-[10px] leading-tight text-slate-400 dark:text-slate-500 mt-2">Aggregate count of all module exams completed. Students can have multiple records.</p>
                </div>

                {{-- Dominant Classification --}}
                <div class="group bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-5 border border-slate-200 dark:border-slate-700/50 hover:border-purple-300 dark:hover:border-purple-500/50 transition-all duration-300 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-500/10 rounded-xl">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    @php
                        $dominantLevel = collect($masteryDistribution)->filter()->sortDesc()->keys()->first() ?? 'N/A';
                        $dominantColors = [
                            'advanced' => 'text-emerald-600 dark:text-emerald-400',
                            'proficient' => 'text-blue-600 dark:text-blue-400',
                            'developing' => 'text-amber-600 dark:text-amber-400',
                            'at_risk' => 'text-red-600 dark:text-red-400',
                        ];
                    @endphp
                    <p class="text-xl font-bold {{ $dominantColors[$dominantLevel] ?? 'text-slate-800 dark:text-white' }}">{{ ucfirst(str_replace('_', ' ', $dominantLevel)) }}</p>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">Dominant Classification</p>
                    <p class="text-[10px] leading-tight text-slate-400 dark:text-slate-500 mt-2">Most common ML classification across all students and modules.</p>
                </div>
            </div>

            {{-- MODULES Section --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-3">
                    <span class="p-2 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                    MODULES
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($moduleStats as $module)
                    @php
                        $gradient = $gradientMap[$module['id']] ?? 'from-gray-500 to-slate-600';
                    @endphp
                    <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-200 dark:border-slate-700/50 hover:shadow-xl hover:border-blue-300 dark:hover:border-blue-500/50 transition-all duration-300 overflow-hidden">
                        {{-- Module Header with Gradient --}}
                        <div class="bg-gradient-to-r {{ $gradient }} p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white text-lg">{{ $module['name'] }}</h4>
                                        <p class="text-white/80 text-sm">{{ $module['questions_count'] }} questions</p>
                                    </div>
                                </div>
                                @php
                                    // Find dominant classification for this module's students
                                    $modulePerfs = $allPerformances->where('module_id', $module['id']);
                                    $modDominantLevel = $modulePerfs->pluck('mastery_level')->countBy()->sortDesc()->keys()->first();
                                    $modLevelColors = [
                                        'advanced' => 'text-emerald-200',
                                        'proficient' => 'text-blue-200',
                                        'developing' => 'text-amber-200',
                                        'at_risk' => 'text-red-200',
                                    ];
                                @endphp
                                <span class="px-3 py-1.5 text-sm font-bold rounded-full bg-white/20 backdrop-blur-sm {{ $modLevelColors[$modDominantLevel] ?? 'text-white' }}">
                                    {{ $modDominantLevel ? ucfirst(str_replace('_', ' ', $modDominantLevel)) : 'No Data' }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Module Stats --}}
                        <div class="p-5">
                            <div class="grid grid-cols-2 gap-4 mb-5">
                                <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $module['student_count'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Students</p>
                                </div>
                                <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                                    <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $module['avg_score'] }}%</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Avg Score</p>
                                </div>
                            </div>
                            
                            {{-- LMS Progress Bar --}}
                            <div class="mb-4">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500 dark:text-slate-400">Class Mastery</span>
                                    <span class="font-medium 
                                        @if($module['avg_lms'] >= 76) text-emerald-600 dark:text-emerald-400
                                        @elseif($module['avg_lms'] >= 56) text-blue-600 dark:text-blue-400
                                        @elseif($module['avg_lms'] >= 36) text-amber-600 dark:text-amber-400
                                        @else text-red-600 dark:text-red-400
                                        @endif
                                    ">{{ $module['avg_lms'] }}/100</span>
                                </div>
                                <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 bg-gradient-to-r {{ $gradient }}" 
                                         style="width: {{ $module['avg_lms'] }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Settings Button --}}
                            <a href="{{ route('instructor.module.settings', ['module' => $module['id']]) }}" 
                               class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-slate-700/50 hover:bg-blue-500 text-slate-600 dark:text-slate-300 hover:text-white rounded-xl font-medium transition-all duration-200 group">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Configure Module
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                
                {{-- Mastery Level Distribution --}}
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-6 border border-slate-200 dark:border-slate-700/50 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        Mastery Distribution
                    </h3>
                    <div class="space-y-4">
                        {{-- Advanced --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    Advanced (76-100)
                                </span>
                                <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $masteryDistribution['advanced'] }}</span>
                            </div>
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-500" 
                                     style="width: {{ $totalPerformanceRecords > 0 ? ($masteryDistribution['advanced'] / $totalPerformanceRecords) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        {{-- Proficient --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                    Proficient (56-75)
                                </span>
                                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $masteryDistribution['proficient'] }}</span>
                            </div>
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full transition-all duration-500" 
                                     style="width: {{ $totalPerformanceRecords > 0 ? ($masteryDistribution['proficient'] / $totalPerformanceRecords) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        {{-- Developing --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                    Developing (36-55)
                                </span>
                                <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ $masteryDistribution['developing'] }}</span>
                            </div>
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 rounded-full transition-all duration-500" 
                                     style="width: {{ $totalPerformanceRecords > 0 ? ($masteryDistribution['developing'] / $totalPerformanceRecords) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        {{-- At Risk --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                    At Risk (0-35)
                                </span>
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $masteryDistribution['at_risk'] }}</span>
                            </div>
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-red-500 to-red-400 rounded-full transition-all duration-500" 
                                     style="width: {{ $totalPerformanceRecords > 0 ? ($masteryDistribution['at_risk'] / $totalPerformanceRecords) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Feature Insights --}}
                <div class="lg:col-span-2 bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-6 border border-slate-200 dark:border-slate-700/50 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Class Performance Insights (All Modules)
                    </h3>
                    @if(count($featureAverages) > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['score_percentage'] }}%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Avg Score</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['hard_question_accuracy'] }}%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hard Q Accuracy</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['hint_usage_percentage'] }}%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hint Usage</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['avg_confidence'] }}/5</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Avg Confidence</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['avg_time_per_question'] }}s</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Time/Question</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600/30">
                            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $featureAverages['review_percentage'] }}%</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Review Rate</p>
                        </div>
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-lg font-medium">No Performance Data Yet</p>
                        <p class="text-sm mt-1">Insights will appear once students complete exams</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Students Table --}}
            <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700/50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Student Performance (All Modules)
                        </h3>
                        <div class="flex gap-2">
                            <button onclick="filterStudents('all')" class="filter-btn active px-3 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white transition-all" data-filter="all">All</button>
                            <button onclick="filterStudents('completed')" class="filter-btn px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all" data-filter="completed">Assessed</button>
                            <button onclick="filterStudents('pending')" class="filter-btn px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all" data-filter="pending">Pending</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700/30">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modules</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Classification</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Avg Score</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Overall Level</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                            @forelse($students as $student)
                                @php
                                    $performances = $student->modulePerformances;
                                    $hasPerformance = $performances->isNotEmpty();
                                    $bestLMS = $hasPerformance ? $performances->max('learning_mastery_score') : null;
                                    $avgScore = $hasPerformance ? $performances->avg('score_percentage') : null;
                                    // Use ML classification directly instead of deriving from LMS range
                                    $overallLevel = $hasPerformance ? $performances->sortByDesc('learning_mastery_score')->first()->mastery_level : null;
                                @endphp
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors student-row" 
                                    data-status="{{ $hasPerformance ? 'completed' : 'pending' }}">
                                    {{-- Student Info --}}
                                    <td class="px-6 py-4">
                                        <a href="{{ route('instructor.student.show', $student) }}" class="flex items-center gap-3 group">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow group-hover:shadow-lg transition-all">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $student->name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $student->student_id }}</p>
                                            </div>
                                        </a>
                                    </td>
                                    
                                    {{-- Status --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($hasPerformance)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                Assessed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                Pending
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Modules Count --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($hasPerformance)
                                            <span class="font-semibold text-slate-800 dark:text-white">{{ $performances->count() }}</span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">/{{ count($modules) }}</span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">0/{{ count($modules) }}</span>
                                        @endif
                                    </td>

                                    {{-- Classification --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($hasPerformance)
                                            @php
                                                $clBadge = [
                                                    'advanced' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-400', 'icon' => '🏆'],
                                                    'proficient' => ['bg' => 'bg-blue-100 dark:bg-blue-500/10', 'text' => 'text-blue-700 dark:text-blue-400', 'icon' => '📘'],
                                                    'developing' => ['bg' => 'bg-amber-100 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-400', 'icon' => '📙'],
                                                    'at_risk' => ['bg' => 'bg-red-100 dark:bg-red-500/10', 'text' => 'text-red-700 dark:text-red-400', 'icon' => '⚠️'],
                                                ][$overallLevel] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => '📊'];
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $clBadge['bg'] }} {{ $clBadge['text'] }}">
                                                {{ $clBadge['icon'] }} {{ ucfirst(str_replace('_', ' ', $overallLevel)) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>

                                    {{-- Avg Score --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($hasPerformance)
                                            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ number_format($avgScore, 1) }}%</span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>

                                    {{-- Overall Level --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($hasPerformance)
                                            @php
                                                $levelConfig = [
                                                    'advanced' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-500/20', 'label' => 'Advanced'],
                                                    'proficient' => ['bg' => 'bg-blue-100 dark:bg-blue-500/10', 'text' => 'text-blue-700 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-500/20', 'label' => 'Proficient'],
                                                    'developing' => ['bg' => 'bg-amber-100 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-500/20', 'label' => 'Developing'],
                                                    'at_risk' => ['bg' => 'bg-red-100 dark:bg-red-500/10', 'text' => 'text-red-700 dark:text-red-400', 'border' => 'border-red-200 dark:border-red-500/20', 'label' => 'At Risk'],
                                                ];
                                                $config = $levelConfig[$overallLevel];
                                            @endphp
                                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }} border {{ $config['border'] }}">
                                                {{ $config['label'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-500 border border-slate-200 dark:border-slate-600/30">
                                                Not Assessed
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 text-center">
                                        <button class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700/50 hover:bg-indigo-500 text-slate-500 dark:text-slate-400 hover:text-white transition-all duration-200" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center text-slate-400">
                                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                            <p class="text-lg font-medium">No Students Found</p>
                                            <p class="text-sm mt-1">Students will appear here once they register</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript for filtering --}}
    <script>
        function filterStudents(filter) {
            const rows = document.querySelectorAll('.student-row');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update button styles
            buttons.forEach(btn => {
                if (btn.dataset.filter === filter) {
                    btn.classList.remove('bg-slate-200', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300', 'hover:bg-slate-300', 'dark:hover:bg-slate-600');
                    btn.classList.add('bg-indigo-600', 'text-white', 'active');
                } else {
                    btn.classList.remove('bg-indigo-600', 'text-white', 'active');
                    btn.classList.add('bg-slate-200', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300', 'hover:bg-slate-300', 'dark:hover:bg-slate-600');
                }
            });
            
            // Filter rows
            rows.forEach(row => {
                const status = row.dataset.status;
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        // Export dropdown: close on outside click
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('exportDropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    </script>
</x-app-layout>
