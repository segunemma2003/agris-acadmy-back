<?php

namespace App\Services;

use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\Course;
use App\Models\CourseRecommendation;
use App\Models\Enrollment;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    /** Chatbot "what do you want to learn about?" answers → words to look for in a course. */
    private const INTEREST_KEYWORDS = [
        'Crops & vegetables' => ['crop', 'vegetable', 'maize', 'rice', 'tomato', 'pepper', 'soil', 'horticulture', 'agronomy', 'seed', 'farming'],
        'Poultry & livestock' => ['poultry', 'livestock', 'chicken', 'broiler', 'layer', 'goat', 'sheep', 'cattle', 'pig', 'animal', 'dairy'],
        'Fish farming' => ['fish', 'aquaculture', 'catfish', 'tilapia', 'pond'],
        'Hydroponics & urban farming' => ['hydroponic', 'urban', 'greenhouse', 'vertical', 'aquaponic', 'soilless'],
        'Agribusiness & marketing' => ['business', 'market', 'finance', 'entrepreneur', 'value chain', 'export', 'processing', 'record'],
        'Agri-tech & smart farming' => ['tech', 'smart', 'digital', 'drone', 'iot', 'sensor', 'data', 'precision', 'irrigation'],
    ];

    /** Strength signals from quiz scores at or above this % count as "doing well". */
    private const STRONG_SCORE = 70;

    /** …and below this % as "finding it hard" (suggest gentler, beginner-level courses). */
    private const WEAK_SCORE = 50;

    /** Academy profile "value chain track" → chatbot interest, for learners who never chatted with COCO. */
    private const TRACK_TO_INTEREST = [
        'poultry' => 'Poultry & livestock',
        'aquaculture' => 'Fish farming',
        'horticulture' => 'Crops & vegetables',
        'cassava_maize' => 'Crops & vegetables',
        'agro_processing' => 'Agribusiness & marketing',
        'agri_logistics' => 'Agri-tech & smart farming',
    ];

    public function topThree(ChatbotSession $session): array
    {
        $answers = $session->intakeAnswer;
        $user = $session->user_id ? User::find($session->user_id) : ($answers?->user_id ? User::find($answers->user_id) : null);

        return $this->rank($answers, $user, 3);
    }

    /**
     * COCO's picks for a signed-in learner (no chat needed): their latest COCO
     * answers if any, else their Academy profile, plus how they're doing on the platform.
     */
    public function forUser(User $user, int $limit = 3): array
    {
        $answers = ChatbotIntakeAnswer::where('user_id', $user->id)->latest()->first()
            ?? $this->answersFromProfile($user);

        return $this->rank($answers, $user, $limit);
    }

    /** Interests + strengths → the best $limit courses, each with a label and a reason. */
    private function rank(?ChatbotIntakeAnswer $answers, ?User $user, int $limit): array
    {
        $signals = $user ? $this->learnerSignals($user) : null;

        $courses = Course::with('category')
            ->where('is_published', true)
            ->when(! $user?->isStaff(), fn ($q) => $q->where('is_staff_only', false))
            ->when($signals, fn ($q) => $q->whereNotIn('id', $signals['enrolled']))
            ->get();

        if ($courses->isEmpty()) {
            return [];
        }

        $hasAnswers = $answers && ! $this->allSkipped($answers);
        if (! $hasAnswers && ! $signals) {
            return $this->fallback($courses, $limit);
        }

        $scored = $courses->map(fn ($course) => [
            'course' => $course,
            'score' => ($hasAnswers ? $this->score($course, $answers) : 0.0) + ($signals ? $this->strengthScore($course, $signals) : 0.0),
        ])->sortByDesc('score')->values();

        $top = $scored->take($limit);

        if ($top->every(fn ($item) => $item['score'] === 0.0)) {
            return $this->fallback($courses, $limit);
        }

        return $top->map(function ($item, $index) use ($answers, $hasAnswers, $signals) {
            $course = $item['course'];

            return [
                'label' => $index === 0 ? 'Best match' : 'Also relevant',
                'score' => round($item['score'], 2),
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->image,
                    'rationale' => $this->buildRationale($course, $hasAnswers ? $answers : null, $signals),
                    'estimated_hours' => $this->minutesToHours($course->duration_minutes),
                    'languages' => $this->parseLang($course->language),
                    'category' => $course->category?->name,
                    'level' => $course->level,
                ],
            ];
        })->values()->toArray();
    }

    /**
     * How the learner is doing: courses they're in or finished, admin-set "next
     * course" links from those, and quiz averages / completions per category.
     *
     * @return array{enrolled: list<int>, next: array<int, string>, categories: array<int, array{avg: ?float, completed: int, levels: list<string>}>}
     */
    public function learnerSignals(User $user): array
    {
        $enrollments = Enrollment::where('user_id', $user->id)->with('course:id,title,category_id,level')->get();
        $completed = $enrollments->filter(fn ($e) => $e->status === 'completed' || (float) $e->progress_percentage >= 100);

        // Admin-configured "after this course, take…" links (completed courses first)
        $next = [];
        foreach (CourseRecommendation::whereIn('course_id', $enrollments->pluck('course_id'))->get() as $rec) {
            $from = $enrollments->firstWhere('course_id', $rec->course_id);
            $isDone = $completed->contains('course_id', $rec->course_id);
            if (! isset($next[$rec->recommended_course_id]) || $isDone) {
                $next[$rec->recommended_course_id] = ($isDone ? 'done:' : 'in:') . ($from?->course?->title ?? 'your course');
            }
        }

        // Quiz averages per category (module + topic quizzes)
        $scores = TestAttempt::query()
            ->join('module_tests', 'module_tests.id', '=', 'test_attempts.module_test_id')
            ->join('courses', 'courses.id', '=', 'module_tests.course_id')
            ->where('test_attempts.user_id', $user->id)->whereNotNull('test_attempts.completed_at')
            ->get(['courses.category_id', 'test_attempts.percentage'])
            ->concat(TopicTestAttempt::query()
                ->join('topic_tests', 'topic_tests.id', '=', 'topic_test_attempts.topic_test_id')
                ->join('courses', 'courses.id', '=', 'topic_tests.course_id')
                ->where('topic_test_attempts.user_id', $user->id)->whereNotNull('topic_test_attempts.completed_at')
                ->get(['courses.category_id', 'topic_test_attempts.percentage']));

        $categories = [];
        foreach ($scores->groupBy('category_id') as $categoryId => $rows) {
            $categories[(int) $categoryId] = ['avg' => round((float) $rows->avg('percentage'), 1), 'completed' => 0, 'levels' => []];
        }
        foreach ($completed as $e) {
            $cid = (int) $e->course?->category_id;
            $categories[$cid] ??= ['avg' => null, 'completed' => 0, 'levels' => []];
            $categories[$cid]['completed']++;
            if ($e->course?->level) {
                $categories[$cid]['levels'][] = strtolower($e->course->level);
            }
        }

        return ['enrolled' => $enrollments->pluck('course_id')->all(), 'next' => $next, 'categories' => $categories];
    }

    /** Extra points from how the learner is doing on the platform. */
    public function strengthScore(Course $course, array $signals): float
    {
        $score = 0.0;

        if (isset($signals['next'][$course->id])) {
            $score += str_starts_with($signals['next'][$course->id], 'done:') ? 6 : 3;
        }

        $cat = $signals['categories'][(int) $course->category_id] ?? null;
        if ($cat) {
            $level = strtolower((string) $course->level);
            $strong = $cat['completed'] > 0 || ($cat['avg'] !== null && $cat['avg'] >= self::STRONG_SCORE);
            $weak = $cat['completed'] === 0 && $cat['avg'] !== null && $cat['avg'] < self::WEAK_SCORE;

            if ($strong) {
                $score += 3;
                // Step up a level after finishing one
                if ($level === 'intermediate' && in_array('beginner', $cat['levels'], true)) $score += 2;
                if ($level === 'advanced' && in_array('intermediate', $cat['levels'], true)) $score += 2;
            } elseif ($weak && $level === 'beginner') {
                $score += 2; // a gentler course to build confidence
            }
        }

        return $score;
    }

    /** Academy profile answers as COCO intake answers (not saved). */
    private function answersFromProfile(User $user): ?ChatbotIntakeAnswer
    {
        $interest = self::TRACK_TO_INTEREST[$user->value_chain_track ?? ''] ?? null;
        if (! $interest) {
            return null;
        }

        return new ChatbotIntakeAnswer(['user_id' => $user->id, 'interest' => $interest]);
    }

    public function score(Course $course, ChatbotIntakeAnswer $answers): float
    {
        $weights = config('chatbot_weights');
        $score = 0.0;

        $categorySlug = $course->category?->slug ?? '';

        // Topic of interest — matched on the course's own words, so it works
        // whatever the categories are called. Strongest signal.
        if ($answers->interest) {
            $haystack = strtolower($course->title.' '.$course->short_description.' '.($course->category?->name ?? '').' '.$categorySlug);
            $keywords = self::INTEREST_KEYWORDS[$answers->interest] ?? [strtolower($answers->interest)];
            foreach ($keywords as $word) {
                if (str_contains($haystack, $word)) {
                    $score += 5;
                    break;
                }
            }
        }

        // Occupation weight
        if ($answers->occupation) {
            $occupationWeights = $weights['occupation'][$answers->occupation] ?? [];
            $score += $occupationWeights[$categorySlug] ?? 0;
        }

        // Goal weight
        if ($answers->learning_goal) {
            $goalWeights = $weights['goal'][$answers->learning_goal] ?? [];
            $score += $goalWeights[$categorySlug] ?? 0;
        }

        // Experience weight
        if ($answers->experience_level && $course->level) {
            $expWeights = $weights['experience'][$answers->experience_level] ?? [];
            $score += $expWeights[strtolower($course->level)] ?? 0;
        }

        // Language weight
        if ($answers->preferred_language && $course->language) {
            $langWeights = $weights['language'][$answers->preferred_language] ?? [];
            $score += $langWeights[strtolower($course->language)] ?? 0;
        }

        return $score;
    }

    public function buildRationale(Course $course, ?ChatbotIntakeAnswer $answers, ?array $signals = null): string
    {
        $category = $course->category?->name ?? 'agriculture';
        $level = $course->level ? strtolower($course->level).' ' : '';
        $parts = [];

        if ($answers?->interest) {
            $parts[] = 'matches your interest in '.strtolower($answers->interest);
        }
        if ($answers?->learning_goal && $answers->learning_goal !== 'Other') {
            $parts[] = 'helps you '.lcfirst($answers->learning_goal);
        }

        // How they're doing on the platform
        if ($signals) {
            $next = $signals['next'][$course->id] ?? null;
            $cat = $signals['categories'][(int) $course->category_id] ?? null;
            if ($next) {
                [$state, $from] = explode(':', $next, 2);
                $parts[] = ($state === 'done' ? 'is the recommended next step after ' : 'goes well with ').$from;
            } elseif ($cat && ($cat['completed'] > 0 || ($cat['avg'] ?? 0) >= self::STRONG_SCORE)) {
                $parts[] = 'builds on your strong results in '.strtolower($category);
            } elseif ($cat && $cat['avg'] !== null && $cat['avg'] < self::WEAK_SCORE && strtolower((string) $course->level) === 'beginner') {
                $parts[] = 'strengthens the basics of '.strtolower($category);
            }
        }

        return $parts
            ? ucfirst(implode(' and ', $parts)).". A {$level}{$category} course."
            : "A {$level}{$category} course popular with learners like you.";
    }

    private function fallback(Collection $courses, int $limit = 3): array
    {
        $top = $courses->sortByDesc('enrollment_count')->take($limit)->values();
        $labels = ['Best match', 'Also relevant', 'Also relevant'];

        return $top->map(function ($course, $index) use ($labels) {
            return [
                'label'  => $labels[$index] ?? 'Also relevant',
                'score'  => 0.0,
                'course' => [
                    'id'              => $course->id,
                    'title'           => $course->title,
                    'thumbnail'       => $course->image,
                    'rationale'       => "One of our most popular {$course->category?->name} courses.",
                    'estimated_hours' => $this->minutesToHours($course->duration_minutes),
                    'languages'       => $this->parseLang($course->language),
                    'category'        => $course->category?->name,
                    'level'           => $course->level,
                ],
            ];
        })->toArray();
    }

    private function allSkipped(ChatbotIntakeAnswer $answers): bool
    {
        return !$answers->occupation
            && !$answers->interest
            && !$answers->learning_goal
            && !$answers->experience_level
            && !$answers->preferred_language;
    }

    private function minutesToHours(?int $minutes): ?string
    {
        if (!$minutes) {
            return null;
        }
        $hours = round($minutes / 60, 1);

        return $hours . ' ' . ($hours === 1.0 ? 'hour' : 'hours');
    }

    private function parseLang(?string $lang): array
    {
        if (!$lang) {
            return [];
        }

        return array_map('trim', explode(',', strtolower($lang)));
    }
}
