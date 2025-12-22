<x-filament-panels::page>
    <div class="space-y-6">
        @if($currentView === 'videos')
            <!-- Videos Section -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-blue-900 dark:text-blue-100 mb-2">
                    Watch Required Videos
                </h2>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    Please watch all three onboarding videos before you can take the quiz. Click "Mark as Complete" after watching each video.
                </p>
            </div>

            @php
                $videos = $this->getVideos();
                $totalVideos = count($videos);
                $watchedCount = count($watchedVideos);
            @endphp

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Progress:</strong> {{ $watchedCount }} of {{ $totalVideos }} videos completed
                    @if($allVideosWatched)
                        <span class="ml-2 text-green-600 dark:text-green-400 font-semibold">✓ All videos watched!</span>
                    @endif
                </p>
            </div>

            <div class="space-y-6">
                @foreach($videos as $index => $video)
                    @php
                        $isWatched = in_array($video['id'], $watchedVideos);
                    @endphp
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Video {{ $index + 1 }}: {{ $video['title'] }}
                                </h3>
                                @if($isWatched)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">
                                        ✓ Watched
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Not Watched
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="aspect-video w-full bg-gray-900 rounded-lg overflow-hidden">
                                <iframe
                                    src="{{ $video['embed_url'] }}?enablejsapi=1"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="w-full h-full"
                                ></iframe>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            @if($isWatched)
                                <x-filament::button disabled color="success" size="sm">
                                    ✓ Completed
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    wire:click="markVideoComplete('{{ $video['id'] }}')"
                                    color="primary"
                                    size="sm"
                                >
                                    Mark as Complete
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($allVideosWatched)
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
                                ✓ All Videos Completed!
                            </h3>
                            <p class="text-sm text-green-800 dark:text-green-200">
                                You have watched all required videos. You can now proceed to take the quiz.
                            </p>
                        </div>
                        <x-filament::button wire:click="startQuiz" color="success" size="lg">
                            Start Quiz
                        </x-filament::button>
                    </div>
                </div>
            @endif

        @elseif($currentView === 'quiz' && !$showResults)
            <!-- Quiz Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-blue-900 dark:text-blue-100 mb-2">
                            Agrisiti × TagDev Program – Comprehensive Onboarding Quiz
                        </h2>
                        <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <p><strong>Passing Score:</strong> 70%</p>
                            <p><strong>Retakes:</strong> Allowed</p>
                            <p><strong>Question Type:</strong> Multiple-choice, single answer</p>
                        </div>
                    </div>
                    <x-filament::button wire:click="$set('currentView', 'videos')" color="gray" size="sm">
                        Back to Videos
                    </x-filament::button>
                </div>
            </div>

            @if(count($attemptHistory) > 0)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quiz Attempt History</h3>
                    <div class="space-y-3">
                        @foreach($attemptHistory as $historyAttempt)
                            <div class="flex items-center justify-between p-3 rounded-lg {{ $historyAttempt->is_passed ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Attempt #{{ $loop->iteration }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $historyAttempt->completed_at ? $historyAttempt->completed_at->format('M d, Y g:i A') : 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-lg font-bold {{ $historyAttempt->is_passed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($historyAttempt->percentage, 1) }}%
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            ({{ $historyAttempt->score }}/{{ $historyAttempt->total_questions }})
                                        </span>
                                        @if($historyAttempt->is_passed)
                                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 font-medium">
                                                ✓ Passed
                                            </span>
                                        @else
                                            <span class="text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 font-medium">
                                                ✗ Not Passed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Quiz Form -->
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" size="lg">
                        Submit Quiz
                    </x-filament::button>
                </div>
            </form>
        @elseif($showResults)
            <!-- Results View -->
            <div class="space-y-6">
                @if($attempt)
                    <div class="rounded-lg border p-6 {{ $attempt->is_passed ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }}">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold mb-2 {{ $attempt->is_passed ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100' }}">
                                @if($attempt->is_passed)
                                    ✓ Congratulations! You Passed!
                                @else
                                    Quiz Completed
                                @endif
                            </h3>
                            <div class="mt-4 space-y-2">
                                <p class="text-lg">
                                    <strong>Score:</strong> {{ $attempt->score }}/{{ $attempt->total_questions }}
                                </p>
                                <p class="text-2xl font-bold">
                                    {{ number_format($attempt->percentage, 1) }}%
                                </p>
                                @if(!$attempt->is_passed)
                                    <p class="text-sm text-red-700 dark:text-red-300">
                                        You need 70% to pass. You can retake the quiz.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Answer Review - Only incorrect answers -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Answer Review - Incorrect Answers</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Below are the questions you answered incorrectly. The correct answer is highlighted in green, and your answer is highlighted in red.
                        </p>
                        @php
                            $questions = $this->questions;
                            $userAnswers = $attempt->answers;
                        @endphp
                        @foreach($questions as $index => $question)
                            @php
                                $questionNumber = $index + 1;
                                $questionId = $question['id'];
                                $userAnswer = $userAnswers[$questionId] ?? null;
                                $isCorrect = $userAnswer === $question['correct_answer'];
                            @endphp
                            @if(!$isCorrect)
                            <div class="border rounded-lg p-4 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-semibold">Question {{ $questionNumber }}</h4>
                                    <span class="text-red-600 dark:text-red-400">✗ Incorrect</span>
                                </div>
                                <p class="mb-3">{{ $question['question'] }}</p>
                                <div class="space-y-1 text-sm mb-4">
                                    @foreach($question['options'] as $key => $option)
                                        @php
                                            $isUserAnswer = $userAnswer === $key;
                                            $isCorrectAnswer = $question['correct_answer'] === $key;
                                        @endphp
                                        <div class="p-2 rounded {{ $isCorrectAnswer ? 'bg-green-100 dark:bg-green-900/40 font-semibold' : ($isUserAnswer && !$isCorrect ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-50 dark:bg-gray-800') }}">
                                            <strong>{{ $key }}.</strong> {{ $option }}
                                            @if($isCorrectAnswer)
                                                <span class="ml-2 text-green-600 dark:text-green-400">✓ Correct Answer</span>
                                            @endif
                                            @if($isUserAnswer && !$isCorrect)
                                                <span class="ml-2 text-red-600 dark:text-red-400">✗ Your Answer</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @php
                                    $correctAnswerKey = $question['correct_answer'];
                                    $correctAnswerText = $question['options'][$correctAnswerKey] ?? '';
                                @endphp
                                <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-sm">
                                    <strong>Correct Answer: {{ $correctAnswerKey }} - {{ $correctAnswerText }}</strong>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <x-filament::button wire:click="$set('currentView', 'videos')" color="gray" size="lg">
                            Back to Videos
                        </x-filament::button>
                        <x-filament::button wire:click="retakeQuiz" color="primary" size="lg">
                            Retake Quiz
                        </x-filament::button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>

