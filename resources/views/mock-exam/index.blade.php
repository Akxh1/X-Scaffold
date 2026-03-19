<style>
    .hint-styled-content ol { counter-reset: hint-step; list-style: none; padding-left: 0; margin: 0; }
    .hint-styled-content ol > li { counter-increment: hint-step; position: relative; padding: 0.75rem 0.75rem 0.75rem 2.5rem; margin-bottom: 0.5rem; background: #f8fafc; border-left: 3px solid #8b5cf6; border-radius: 0.5rem; font-size: 0.875rem; line-height: 1.5; }
    .dark .hint-styled-content ol > li { background: rgba(51, 65, 85, 0.3); border-left-color: #a78bfa; }
    .hint-styled-content ol > li::before { content: counter(hint-step); position: absolute; left: 0.65rem; top: 0.75rem; width: 1.25rem; height: 1.25rem; background: #8b5cf6; color: white; border-radius: 50%; font-size: 0.7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .hint-styled-content ul { list-style: none; padding-left: 0; margin: 0; }
    .hint-styled-content ul > li { position: relative; padding: 0.625rem 0.75rem 0.625rem 1.75rem; margin-bottom: 0.375rem; background: #f8fafc; border-radius: 0.5rem; font-size: 0.875rem; line-height: 1.5; }
    .dark .hint-styled-content ul > li { background: rgba(51, 65, 85, 0.3); }
    .hint-styled-content ul > li::before { content: "\2022"; position: absolute; left: 0.65rem; top: 0.5rem; color: #8b5cf6; font-size: 1.25rem; font-weight: 700; }
    .hint-styled-content p { margin-bottom: 0.75rem; font-size: 0.875rem; line-height: 1.6; }
    .hint-styled-content p:last-child { margin-bottom: 0; }
    .hint-styled-content em { color: #7c3aed; font-style: italic; }
    .dark .hint-styled-content em { color: #c4b5fd; }
    .hint-styled-content strong { color: #1e293b; }
    .dark .hint-styled-content strong { color: #e2e8f0; }
</style>
<x-app-layout>
    {{-- Mock Exam - Main Exam Interface --}}
    <div x-data="mockExam()"
         x-init="init()"
         @visibilitychange.window="handleVisibilityChange()"
         class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-purple-900">

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            {{-- Header Bar --}}
            <div class="bg-gradient-to-r from-purple-500 to-violet-600 rounded-2xl p-4 mb-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <i class="fas fa-magic text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white">Mock Exam</h1>
                            <p class="text-white/80 text-sm">{{ $moduleData['title'] }} • Attempt {{ $attemptNumber }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-white">
                        @if(!$hasLevelIndicatorData)
                        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-amber-500/30 rounded-lg text-xs">
                            <i class="fas fa-info-circle"></i>
                            <span>Generic hints mode</span>
                        </div>
                        @endif
                        <div class="text-center">
                            <div class="text-2xl font-bold" x-text="formatTime(elapsedTime)">00:00</div>
                            <div class="text-xs text-white/70">Time</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold" x-text="(current + 1) + '/' + questions.length">1/10</div>
                            <div class="text-xs text-white/70">Question</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mb-6">
                <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-violet-500 rounded-full transition-all duration-300"
                         :style="'width: ' + ((current + 1) / questions.length * 100) + '%'"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>Question <span x-text="current + 1"></span></span>
                    <span x-text="Math.round((current + 1) / questions.length * 100) + '% Complete'"></span>
                </div>
            </div>

            {{-- Question Card --}}
            <template x-if="questions.length > 0">
                <div class="bg-white dark:bg-slate-800/80 rounded-3xl shadow-xl overflow-hidden border border-slate-200 dark:border-slate-700">

                    {{-- Question Header --}}
                    <div class="bg-slate-50 dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-purple-500 text-white flex items-center justify-center font-bold"
                                      x-text="current + 1"></span>
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide"
                                          x-text="questions[current].type.replace('_', ' ')"></span>
                                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': questions[current].difficulty == 1,
                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': questions[current].difficulty == 2,
                                              'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': questions[current].difficulty >= 3
                                          }"
                                          x-text="questions[current].difficulty == 1 ? 'Easy' : (questions[current].difficulty == 2 ? 'Medium' : 'Hard')">
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Review Flag --}}
                                <button type="button" @click="toggleReview(questions[current].id)"
                                        class="p-2 rounded-lg transition"
                                        :class="tracking.reviewMarked.has(questions[current].id)
                                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                            : 'bg-slate-100 text-slate-400 dark:bg-slate-700 hover:bg-amber-50 hover:text-amber-500'">
                                    <i class="fas fa-flag"></i>
                                </button>
                                {{-- Hint Button --}}
                                <button type="button" @click="requestHint(questions[current].id)"
                                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition"
                                        :title="hasLevelIndicatorData ? 'Request Adaptive Hint' : 'Request Generic Hint'">
                                    <i class="fas fa-lightbulb"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Question Body --}}
                    <div class="p-6" @click="recordClick(questions[current].id)">
                        <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-6"
                            x-text="questions[current].question_text"></h2>

                        {{-- MCQ Options --}}
                        <template x-if="questions[current].type === 'mcq'">
                            <div class="space-y-3">
                                <template x-for="answer in questions[current].answers" :key="answer.id">
                                    <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                           :class="userAnswers[questions[current].id] == answer.id
                                               ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                               : 'border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700'"
                                           @click="selectAnswer(questions[current].id, answer.id)">
                                        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition"
                                             :class="userAnswers[questions[current].id] == answer.id
                                                 ? 'border-purple-500 bg-purple-500'
                                                 : 'border-slate-300 dark:border-slate-600'">
                                            <div class="w-2 h-2 rounded-full bg-white"
                                                 x-show="userAnswers[questions[current].id] == answer.id"></div>
                                        </div>
                                        <span class="flex-1 text-slate-700 dark:text-slate-200" x-text="answer.answer_text"></span>
                                    </label>
                                </template>
                            </div>
                        </template>

                        {{-- True/False --}}
                        <template x-if="questions[current].type === 'true_false'">
                            <div class="flex gap-4">
                                <label class="flex-1 flex items-center justify-center gap-3 p-6 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="userAnswers[questions[current].id] === 'true'
                                           ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                           : 'border-slate-200 dark:border-slate-700 hover:border-green-300'"
                                       @click="selectAnswer(questions[current].id, 'true')">
                                    <i class="fas fa-check-circle text-2xl"
                                       :class="userAnswers[questions[current].id] === 'true' ? 'text-green-500' : 'text-slate-400'"></i>
                                    <span class="text-lg font-medium text-slate-700 dark:text-slate-200">True</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-3 p-6 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="userAnswers[questions[current].id] === 'false'
                                           ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                           : 'border-slate-200 dark:border-slate-700 hover:border-red-300'"
                                       @click="selectAnswer(questions[current].id, 'false')">
                                    <i class="fas fa-times-circle text-2xl"
                                       :class="userAnswers[questions[current].id] === 'false' ? 'text-red-500' : 'text-slate-400'"></i>
                                    <span class="text-lg font-medium text-slate-700 dark:text-slate-200">False</span>
                                </label>
                            </div>
                        </template>

                        {{-- Fill in Blank --}}
                        <template x-if="questions[current].type === 'fill_in_blank'">
                            <div>
                                <input type="text"
                                       class="w-full p-4 text-lg rounded-xl border-2 border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition"
                                       placeholder="Type your answer here..."
                                       x-model="userAnswers[questions[current].id]"
                                       @input="recordAnswerChange(questions[current].id)">
                            </div>
                        </template>
                    </div>

                    {{-- Confidence Rating --}}
                    <div class="px-6 pb-6">
                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-3">
                                <i class="fas fa-gauge-high mr-2"></i>
                                How confident are you in your answer?
                            </label>
                            <div class="flex justify-between gap-2">
                                <template x-for="level in [1, 2, 3, 4, 5]" :key="level">
                                    <button type="button"
                                            @click="setConfidence(questions[current].id, level)"
                                            class="flex-1 py-3 rounded-lg font-medium text-sm transition-all"
                                            :class="tracking.confidenceRatings[questions[current].id] === level
                                                ? 'bg-purple-500 text-white shadow-lg'
                                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-600 hover:border-purple-300'">
                                        <span x-text="level"></span>
                                        <span class="hidden sm:inline ml-1"
                                              x-text="level === 1 ? '(Guess)' : (level === 3 ? '(Maybe)' : (level === 5 ? '(Sure)' : ''))"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="px-6 pb-6 flex items-center justify-between gap-4">
                        <button type="button" @click="prevQuestion()"
                                class="px-6 py-3 rounded-xl font-medium transition"
                                :class="current === 0
                                    ? 'bg-slate-100 text-slate-400 cursor-not-allowed dark:bg-slate-800'
                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600'"
                                :disabled="current === 0">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>

                        <div class="flex gap-1 overflow-x-auto px-2">
                            <template x-for="(q, idx) in questions" :key="q.id">
                                <button type="button" @click="goToQuestion(idx)"
                                        class="w-8 h-8 rounded-lg text-xs font-bold transition flex-shrink-0"
                                        :class="{
                                            'bg-purple-500 text-white': current === idx,
                                            'bg-green-500 text-white': current !== idx && userAnswers[q.id],
                                            'bg-amber-500 text-white': current !== idx && !userAnswers[q.id] && tracking.reviewMarked.has(q.id),
                                            'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400': current !== idx && !userAnswers[q.id] && !tracking.reviewMarked.has(q.id)
                                        }"
                                        x-text="idx + 1">
                                </button>
                            </template>
                        </div>

                        <template x-if="current < questions.length - 1">
                            <button type="button" @click="nextQuestion()"
                                    class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-medium transition">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </template>
                        <template x-if="current === questions.length - 1">
                            <button type="button" @click="submitExam()"
                                    :disabled="submitting"
                                    class="px-8 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-semibold transition disabled:opacity-50">
                                <span x-show="!submitting"><i class="fas fa-check mr-2"></i> Submit Exam</span>
                                <span x-show="submitting"><i class="fas fa-spinner fa-spin mr-2"></i> Submitting...</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Upgraded Hint Modal --}}
            <div x-show="showHint" x-transition
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
                     @click.away="showHint = false">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700"
                         :class="{
                             'bg-gradient-to-r from-purple-500 to-violet-600': hintMode === 'personalized',
                             'bg-gradient-to-r from-slate-500 to-slate-600': hintMode === 'generic',
                             'bg-gradient-to-r from-amber-500 to-orange-500': hintMode === 'diagnostic'
                         }">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    <i class="fas text-white text-lg"
                                       :class="hintMode === 'personalized' ? 'fa-brain' : (hintMode === 'generic' ? 'fa-lightbulb' : 'fa-flask')"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-base"
                                        x-text="hintMode === 'personalized' ? 'Adaptive Hint' : (hintMode === 'generic' ? 'Quick Hint' : 'Hint')"></h3>
                                    <p class="text-white/70 text-xs"
                                       x-text="hintMode === 'personalized' ? 'Powered by your Level Indicator analysis' : (hintMode === 'generic' ? 'Complete Level Indicator for personalized hints' : 'Diagnostic mode')"></p>
                                </div>
                                <template x-if="hintLevel && hintMode === 'personalized'">
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-white/20 text-white"
                                          x-text="hintLevel === 1 ? 'Socratic' : (hintLevel === 2 ? 'Guided' : (hintLevel === 3 ? 'Step-by-Step' : 'Full Support'))"></span>
                                </template>
                            </div>
                            <button @click="showHint = false" class="text-white/70 hover:text-white transition p-2">
                                <i class="fas fa-times text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">

                        {{-- Fallback Banner --}}
                        <template x-if="isFallbackHint && !hintLoading">
                            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-500/10 rounded-xl border border-amber-200 dark:border-amber-500/20 flex items-start gap-3">
                                <i class="fas fa-wifi-slash text-amber-500 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Offline Hint</p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">AI service temporarily unavailable. Showing a keyword-based fallback hint.</p>
                                </div>
                            </div>
                        </template>

                        {{-- Student Profile Card (only for personalized mode) --}}
                        <template x-if="hintMode === 'personalized' && studentProfile && !hintLoading">
                            <div class="mb-4 bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl border border-purple-200 dark:border-purple-700/30 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                                        <i class="fas fa-user-graduate mr-1"></i> Your Learning Profile
                                    </span>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                          :class="{
                                              'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': studentProfile.mastery_level === 'advanced' || studentProfile.mastery_level === 'proficient',
                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': studentProfile.mastery_level === 'developing',
                                              'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': studentProfile.mastery_level === 'at_risk'
                                          }"
                                          x-text="(studentProfile.mastery_level || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())">
                                    </span>
                                </div>

                                {{-- LMS + Confidence + Score Row --}}
                                <div class="grid grid-cols-3 gap-2 mb-3">
                                    <div class="bg-white dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                        <p class="text-lg font-bold text-purple-600 dark:text-purple-400" x-text="studentProfile.lms_score"></p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase">LMS Score</p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400" x-text="studentProfile.score_percentage + '%'"></p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase">Exam Score</p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800/50 rounded-lg p-2 text-center">
                                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400" x-text="studentProfile.ml_confidence + '%'"></p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase">ML Confidence</p>
                                    </div>
                                </div>

                                {{-- SHAP Strengths & Weaknesses --}}
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="text-xs" x-show="studentProfile.top_strengths">
                                        <p class="font-semibold text-green-600 dark:text-green-400 mb-1">
                                            <i class="fas fa-arrow-up mr-1"></i>Strengths
                                        </p>
                                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed" x-text="studentProfile.top_strengths"></p>
                                    </div>
                                    <div class="text-xs" x-show="studentProfile.top_weaknesses">
                                        <p class="font-semibold text-red-500 dark:text-red-400 mb-1">
                                            <i class="fas fa-arrow-down mr-1"></i>Areas to Improve
                                        </p>
                                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed" x-text="studentProfile.top_weaknesses"></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Generic Mode Banner --}}
                        <template x-if="hintMode === 'generic' && !hintLoading && !isFallbackHint">
                            <div class="mb-4 p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 flex items-start gap-3">
                                <i class="fas fa-info-circle text-slate-400 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Generic Hint Mode</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Complete the <a href="{{ route('level-indicator.show', $module) }}" class="text-purple-600 dark:text-purple-400 underline hover:no-underline">Level Indicator Exam</a> to unlock personalized adaptive hints with SHAP analysis.</p>
                                </div>
                            </div>
                        </template>

                        {{-- Loading State --}}
                        <template x-if="hintLoading">
                            <div class="flex flex-col items-center justify-center py-10">
                                <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                                    <i class="fas fa-spinner fa-spin text-2xl text-purple-500"></i>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400"
                                   x-text="hintMode === 'personalized' ? 'Generating personalized hint...' : 'Generating hint...'"></p>
                            </div>
                        </template>

                        {{-- Hint Content --}}
                        <template x-if="!hintLoading">
                            <div class="hint-styled-content prose prose-sm dark:prose-invert max-w-none"
                                 x-html="currentHint || '<p class=\'text-slate-400\'>No hint available.</p>'"></div>
                        </template>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider"
                              x-text="hintMode === 'personalized' ? ('Adaptive Scaffolding • ' + (hintLevel === 1 ? 'Socratic' : (hintLevel === 2 ? 'Guided' : (hintLevel === 3 ? 'Step-by-Step' : 'Full Support')))) : (hintMode === 'generic' ? 'Generic Mode • No profile data' : 'Diagnostic Mode')"></span>
                        <button @click="showHint = false"
                                class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mockExam() {
            return {
                // Exam data
                questions: @json($questions),
                moduleId: {{ $module->id }},
                hasLevelIndicatorData: {{ $hasLevelIndicatorData ? 'true' : 'false' }},
                current: 0,
                userAnswers: {},

                // UI state
                submitting: false,
                showHint: false,
                hintLoading: false,
                currentHint: '',
                hintMode: 'generic',
                isFallbackHint: false,
                studentProfile: null,
                elapsedTime: 0,
                timerInterval: null,
                hintLevel: null,

                // ================================================================
                // 11-FEATURE TRACKING SYSTEM (identical to Level Indicator)
                // ================================================================
                tracking: {
                    examStartTime: null,
                    questionStartTimes: {},
                    questionTotalTimes: {},
                    answerHistory: {},
                    tabSwitchCount: 0,
                    clickCounts: {},
                    reviewMarked: new Set(),
                    hintsUsed: new Set(),
                    confidenceRatings: {},
                    firstActionTimestamps: {},
                },

                init() {
                    this.tracking.examStartTime = Date.now();
                    this.startTimer();
                    this.recordQuestionView(this.questions[0].id);

                    this.questions.forEach(q => {
                        this.tracking.answerHistory[q.id] = [];
                        this.tracking.clickCounts[q.id] = 0;
                    });
                },

                startTimer() {
                    this.timerInterval = setInterval(() => {
                        this.elapsedTime = Math.floor((Date.now() - this.tracking.examStartTime) / 1000);
                    }, 1000);
                },

                formatTime(seconds) {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                },

                // ================================================================
                // TRACKING METHODS
                // ================================================================

                recordQuestionView(qId) {
                    if (!this.tracking.questionStartTimes[qId]) {
                        this.tracking.questionStartTimes[qId] = Date.now();
                    }
                },

                recordQuestionLeave(qId) {
                    if (this.tracking.questionStartTimes[qId]) {
                        const timeSpent = (Date.now() - this.tracking.questionStartTimes[qId]) / 1000;
                        this.tracking.questionTotalTimes[qId] = (this.tracking.questionTotalTimes[qId] || 0) + timeSpent;
                        this.tracking.questionStartTimes[qId] = null;
                    }
                },

                recordFirstAction(qId) {
                    if (!this.tracking.firstActionTimestamps[qId]) {
                        const viewStart = this.tracking.questionStartTimes[qId] || Date.now();
                        this.tracking.firstActionTimestamps[qId] = (Date.now() - viewStart) / 1000;
                    }
                },

                recordClick(qId) {
                    this.tracking.clickCounts[qId] = (this.tracking.clickCounts[qId] || 0) + 1;
                    this.recordFirstAction(qId);
                },

                selectAnswer(qId, value) {
                    const oldValue = this.userAnswers[qId];
                    if (oldValue !== undefined && oldValue !== value) {
                        this.tracking.answerHistory[qId].push({
                            old: oldValue,
                            new: value,
                            timestamp: Date.now()
                        });
                    }
                    this.userAnswers[qId] = value;
                    this.recordFirstAction(qId);
                },

                recordAnswerChange(qId) {
                    this.recordFirstAction(qId);
                },

                setConfidence(qId, level) {
                    this.tracking.confidenceRatings[qId] = level;
                    this.recordFirstAction(qId);
                },

                toggleReview(qId) {
                    if (this.tracking.reviewMarked.has(qId)) {
                        this.tracking.reviewMarked.delete(qId);
                    } else {
                        this.tracking.reviewMarked.add(qId);
                    }
                },

                handleVisibilityChange() {
                    if (document.hidden) {
                        this.tracking.tabSwitchCount++;
                    }
                },

                /**
                 * Request hint — uses ADAPTIVE scaffolding (is_diagnostic: false)
                 * if the student has Level Indicator data, otherwise generic.
                 * The HintController reads StudentModulePerformance for XAI context.
                 */
                async requestHint(qId) {
                    this.tracking.hintsUsed.add(qId);
                    this.showHint = true;
                    this.hintLoading = true;
                    this.currentHint = '';
                    this.hintMode = 'generic';
                    this.isFallbackHint = false;
                    this.studentProfile = null;
                    this.hintLevel = null;

                    try {
                        const response = await fetch('/generate-hint', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                question_text: this.questions[this.current].question_text,
                                question_id: this.questions[this.current].id,
                                module_id: this.moduleId,
                                is_diagnostic: false
                            })
                        });
                        const data = await response.json();
                        this.currentHint = data.hint || "Hint couldn't be generated.";
                        this.hintMode = data.hint_mode || 'generic';
                        this.isFallbackHint = data.is_fallback === true;
                        this.studentProfile = data.student_profile || null;
                        this.hintLevel = data.hint_level || null;
                    } catch {
                        this.currentHint = "Hint couldn't be generated.";
                        this.isFallbackHint = true;
                    } finally {
                        this.hintLoading = false;
                    }
                },

                // ================================================================
                // NAVIGATION
                // ================================================================

                nextQuestion() {
                    this.recordQuestionLeave(this.questions[this.current].id);
                    if (this.current < this.questions.length - 1) {
                        this.current++;
                        this.recordQuestionView(this.questions[this.current].id);
                    }
                },

                prevQuestion() {
                    this.recordQuestionLeave(this.questions[this.current].id);
                    if (this.current > 0) {
                        this.current--;
                        this.recordQuestionView(this.questions[this.current].id);
                    }
                },

                goToQuestion(idx) {
                    this.recordQuestionLeave(this.questions[this.current].id);
                    this.current = idx;
                    this.recordQuestionView(this.questions[this.current].id);
                },

                // ================================================================
                // FEATURE CALCULATION
                // ================================================================

                calculateFeatures() {
                    const totalQuestions = this.questions.length;

                    const hintUsagePercentage = (this.tracking.hintsUsed.size / totalQuestions) * 100;

                    const confidenceValues = Object.values(this.tracking.confidenceRatings);
                    const avgConfidence = confidenceValues.length > 0
                        ? confidenceValues.reduce((a, b) => a + b, 0) / confidenceValues.length
                        : 3.0;

                    let totalChanges = 0;
                    Object.values(this.tracking.answerHistory).forEach(history => {
                        totalChanges += history.length;
                    });
                    const answerChangesRate = totalChanges / totalQuestions;

                    const tabSwitchesRate = this.tracking.tabSwitchCount / totalQuestions;

                    // Finalize current question time
                    this.recordQuestionLeave(this.questions[this.current].id);
                    const times = Object.values(this.tracking.questionTotalTimes);
                    const avgTimePerQuestion = times.length > 0
                        ? times.reduce((a, b) => a + b, 0) / times.length
                        : 60;

                    const reviewPercentage = (this.tracking.reviewMarked.size / totalQuestions) * 100;

                    const latencies = Object.values(this.tracking.firstActionTimestamps);
                    const avgFirstActionLatency = latencies.length > 0
                        ? latencies.reduce((a, b) => a + b, 0) / latencies.length
                        : 3.0;

                    const clicks = Object.values(this.tracking.clickCounts);
                    const clicksPerQuestion = clicks.length > 0
                        ? clicks.reduce((a, b) => a + b, 0) / totalQuestions
                        : 4.0;

                    return {
                        hint_usage_percentage: hintUsagePercentage,
                        avg_confidence: avgConfidence,
                        answer_changes_rate: answerChangesRate,
                        tab_switches_rate: tabSwitchesRate,
                        avg_time_per_question: avgTimePerQuestion,
                        review_percentage: reviewPercentage,
                        avg_first_action_latency: avgFirstActionLatency,
                        clicks_per_question: clicksPerQuestion,
                    };
                },

                // ================================================================
                // SUBMISSION
                // ================================================================

                async submitExam() {
                    if (this.submitting) return;

                    const unanswered = this.questions.filter(q => !this.userAnswers[q.id]);
                    if (unanswered.length > 0) {
                        if (!confirm(`You have ${unanswered.length} unanswered question(s). Submit anyway?`)) {
                            return;
                        }
                    }

                    this.submitting = true;
                    clearInterval(this.timerInterval);

                    const features = this.calculateFeatures();
                    const questionIds = this.questions.map(q => q.id);

                    try {
                        const response = await fetch('{{ route("mock-exam.submit", $module) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                answers: this.userAnswers,
                                features: features,
                                question_ids: questionIds
                            })
                        });

                        const data = await response.json();

                        if (data.success && data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            alert('Error submitting exam. Please try again.');
                            this.submitting = false;
                        }
                    } catch (error) {
                        console.error('Submission error:', error);
                        alert('Error submitting exam. Please try again.');
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
