<?php

namespace App\Services;

use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\Course;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function topThree(ChatbotSession $session): array
    {
        $answers = $session->intakeAnswer;
        $courses = Course::with('category')
            ->where('is_published', true)
            ->get();

        if ($courses->isEmpty()) {
            return [];
        }

        // No answers or all skipped → fallback to most enrolled
        if (!$answers || $this->allSkipped($answers)) {
            return $this->fallback($courses);
        }

        $scored = $courses->map(fn ($course) => [
            'course' => $course,
            'score'  => $this->score($course, $answers),
        ])->sortByDesc('score')->values();

        $top = $scored->take(3);

        // If all scores are 0, fall back
        if ($top->every(fn ($item) => $item['score'] === 0.0)) {
            return $this->fallback($courses);
        }

        $labels = ['Best match', 'Also relevant', 'Also relevant'];

        return $top->map(function ($item, $index) use ($labels, $answers) {
            $course = $item['course'];

            return [
                'label'  => $labels[$index] ?? 'Also relevant',
                'score'  => round($item['score'], 2),
                'course' => [
                    'id'              => $course->id,
                    'title'           => $course->title,
                    'thumbnail'       => $course->image,
                    'rationale'       => $this->buildRationale($course, $answers),
                    'estimated_hours' => $this->minutesToHours($course->duration_minutes),
                    'languages'       => $this->parseLang($course->language),
                    'category'        => $course->category?->name,
                    'level'           => $course->level,
                ],
            ];
        })->values()->toArray();
    }

    public function score(Course $course, ChatbotIntakeAnswer $answers): float
    {
        $weights = config('chatbot_weights');
        $score = 0.0;

        $categorySlug = $course->category?->slug ?? '';

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

    public function buildRationale(Course $course, ?ChatbotIntakeAnswer $answers): string
    {
        $experience = $answers?->experience_level ?? 'all';
        $goal = $answers?->learning_goal ?? 'learn farming';
        $category = $course->category?->name ?? 'agriculture';

        // Normalize to lowercase for sentence flow
        $exp = strtolower($experience);
        $goal = strtolower($goal);

        return "Great for {$exp} learners looking to {$goal} with Agrisiti's {$category} programme.";
    }

    private function fallback(Collection $courses): array
    {
        $top = $courses->sortByDesc('enrollment_count')->take(3)->values();
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
