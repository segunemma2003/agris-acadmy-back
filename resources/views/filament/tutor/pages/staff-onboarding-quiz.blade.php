<x-filament-panels::page>
    <div class="space-y-6">
        @if(!$showResults)
            <!-- Quiz Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    Agrisiti × TagDev Program – Comprehensive Onboarding Quiz
                </h3>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <p><strong>Duration:</strong> 3–5 days</p>
                    <p><strong>Passing Score:</strong> 70%</p>
                    <p><strong>Retakes:</strong> Allowed</p>
                    <p class="mt-4"><strong>Goal:</strong> Make sure every new staff member understands the program, their responsibilities, safeguarding and reporting — before they start work.</p>
                </div>
            </div>

            @if($bestScore !== null)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>Your Best Score:</strong> {{ number_format($bestScore, 1) }}%
                        @if($hasPassed)
                            <span class="text-green-600 dark:text-green-400 ml-2">✓ Passed</span>
                        @else
                            <span class="text-red-600 dark:text-red-400 ml-2">✗ Not Passed</span>
                        @endif
                    </p>
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
        @else
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

                    <!-- Answer Review -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Answer Review</h3>
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
                            <div class="border rounded-lg p-4 {{ $isCorrect ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }}">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-semibold">Question {{ $questionNumber }}</h4>
                                    @if($isCorrect)
                                        <span class="text-green-600 dark:text-green-400">✓ Correct</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">✗ Incorrect</span>
                                    @endif
                                </div>
                                <p class="mb-3">{{ $question['question'] }}</p>
                                <div class="space-y-1 text-sm">
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
                                @if(isset($question['explanation']))
                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                                        {{ $question['explanation'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <x-filament::button wire:click="retakeQuiz" color="gray" size="lg">
                            Retake Quiz
                        </x-filament::button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>

