<?php

namespace App\Services;

use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\Course;
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

    public function buildRationale(Course $course, ?ChatbotIntakeAnswer $answers): string
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

        return $parts
            ? ucfirst(implode(' and ', $parts)).". A {$level}{$category} course."
            : "A {$level}{$category} course popular with learners like you.";
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
