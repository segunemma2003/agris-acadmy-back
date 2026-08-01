<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CohortHeatMapService
{
    public const CACHE_TTL_SECONDS = 60 * 60 * 26; // slightly over 1 day

    public const BANDS = [
        'red' => ['min' => 0, 'max' => 25, 'label' => '0–25%'],
        'amber' => ['min' => 26, 'max' => 50, 'label' => '26–50%'],
        'teal' => ['min' => 51, 'max' => 75, 'label' => '51–75%'],
        'green' => ['min' => 76, 'max' => 100, 'label' => '76–100%'],
    ];

    public static function cacheKey(?int $facilitatorId = null): string
    {
        return $facilitatorId
            ? "cohort_heatmap:facilitator:{$facilitatorId}"
            : 'cohort_heatmap:all';
    }

    public static function metaCacheKey(): string
    {
        return 'cohort_heatmap:meta';
    }

    public static function bandFor(float $progress): string
    {
        $progress = max(0, min(100, $progress));

        if ($progress <= 25) {
            return 'red';
        }
        if ($progress <= 50) {
            return 'amber';
        }
        if ($progress <= 75) {
            return 'teal';
        }

        return 'green';
    }

    public static function bandColor(string $band): string
    {
        return match ($band) {
            'red' => '#DC2626',
            'amber' => '#D97706',
            'teal' => '#0D9488',
            'green' => '#16A34A',
            default => '#64748B',
        };
    }

    /**
     * Read cached snapshot. Does not hit the DB for progress (daily refresh).
     * Returns empty payload if the cache has not been warmed yet.
     */
    public function getCached(?int $facilitatorId = null): array
    {
        $payload = Cache::get(self::cacheKey($facilitatorId));

        if (is_array($payload)) {
            return $payload;
        }

        return [
            'generated_at' => null,
            'learners' => [],
            'stale' => true,
        ];
    }

    public function getMeta(): array
    {
        return Cache::get(self::metaCacheKey(), [
            'generated_at' => null,
            'learner_count' => 0,
            'facilitator_count' => 0,
        ]);
    }

    /**
     * Rebuild global + per-facilitator heat map caches from current enrollments.
     */
    public function refreshAll(): array
    {
        $learners = $this->buildLearnerSnapshots();

        Cache::put(self::cacheKey(null), [
            'generated_at' => now()->toIso8601String(),
            'learners' => $learners->values()->all(),
            'stale' => false,
        ], self::CACHE_TTL_SECONDS);

        $byFacilitator = $learners->groupBy(fn (array $row) => $row['facilitator_id'] ?? 'none');

        $facilitatorIds = User::where('role', 'facilitator')->pluck('id');

        foreach ($facilitatorIds as $facilitatorId) {
            $subset = ($byFacilitator->get($facilitatorId) ?? collect())->values()->all();

            Cache::put(self::cacheKey((int) $facilitatorId), [
                'generated_at' => now()->toIso8601String(),
                'learners' => $subset,
                'stale' => false,
            ], self::CACHE_TTL_SECONDS);
        }

        $meta = [
            'generated_at' => now()->toIso8601String(),
            'learner_count' => $learners->count(),
            'facilitator_count' => $facilitatorIds->count(),
        ];

        Cache::put(self::metaCacheKey(), $meta, self::CACHE_TTL_SECONDS);

        return $meta;
    }

    /**
     * Filter a cached learner list in memory (no live DB progress queries).
     *
     * @param  array<int, array<string, mixed>>  $learners
     * @return array<int, array<string, mixed>>
     */
    public function filterLearners(
        array $learners,
        ?int $courseId = null,
        ?int $moduleId = null,
        ?string $band = null,
    ): array {
        $filtered = [];

        foreach ($learners as $learner) {
            $progress = $this->resolveProgress($learner, $courseId, $moduleId);

            if ($progress === null) {
                continue;
            }

            $resolvedBand = self::bandFor($progress);

            if ($band && $resolvedBand !== $band) {
                continue;
            }

            $learner['display_progress'] = round($progress, 1);
            $learner['display_band'] = $resolvedBand;
            $learner['display_band_color'] = self::bandColor($resolvedBand);
            $learner['display_band_label'] = self::BANDS[$resolvedBand]['label'];
            $filtered[] = $learner;
        }

        usort($filtered, fn ($a, $b) => $a['display_progress'] <=> $b['display_progress']);

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $learner
     */
    public function resolveProgress(array $learner, ?int $courseId = null, ?int $moduleId = null): ?float
    {
        $enrollments = $learner['enrollments'] ?? [];

        if ($moduleId) {
            foreach ($enrollments as $enrollment) {
                foreach ($enrollment['modules'] ?? [] as $module) {
                    if ((int) $module['module_id'] === $moduleId) {
                        if ($courseId && (int) $enrollment['course_id'] !== $courseId) {
                            return null;
                        }

                        return (float) $module['progress_percentage'];
                    }
                }
            }

            return null;
        }

        if ($courseId) {
            foreach ($enrollments as $enrollment) {
                if ((int) $enrollment['course_id'] === $courseId) {
                    return (float) $enrollment['progress_percentage'];
                }
            }

            return null;
        }

        return isset($learner['overall_progress'])
            ? (float) $learner['overall_progress']
            : 0.0;
    }

    /**
     * Course / module options present in a cached snapshot (for filter dropdowns).
     *
     * @param  array<int, array<string, mixed>>  $learners
     * @return array{courses: array<int, string>, modules: array<int, array{label: string, course_id: int}>}
     */
    public function filterOptions(array $learners): array
    {
        $courses = [];
        $modules = [];

        foreach ($learners as $learner) {
            foreach ($learner['enrollments'] ?? [] as $enrollment) {
                $courses[(int) $enrollment['course_id']] = $enrollment['course_title'];
                foreach ($enrollment['modules'] ?? [] as $module) {
                    $modules[(int) $module['module_id']] = [
                        'label' => $enrollment['course_title'] . ' — ' . $module['module_title'],
                        'course_id' => (int) $enrollment['course_id'],
                    ];
                }
            }
        }

        asort($courses);

        return [
            'courses' => $courses,
            'modules' => $modules,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLearnerSnapshots(): Collection
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->with([
                'enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])
                    ->with(['course.modules' => fn ($mq) => $mq->where('is_active', true)->with('topics:id,module_id,title')]),
            ])
            ->get(['id', 'name', 'email', 'phone', 'state', 'lga', 'avatar', 'facilitator_id', 'last_active_date', 'occupation', 'gender']);

        $userIds = $students->pluck('id');

        $completedTopicIdsByUserCourse = StudentProgress::query()
            ->whereIn('user_id', $userIds)
            ->where('is_completed', true)
            ->get(['user_id', 'course_id', 'topic_id'])
            ->groupBy(fn ($row) => $row->user_id . ':' . $row->course_id)
            ->map(fn ($rows) => $rows->pluck('topic_id')->all());

        return $students->map(function (User $student) use ($completedTopicIdsByUserCourse) {
            $enrollments = $student->enrollments->map(function (Enrollment $enrollment) use ($completedTopicIdsByUserCourse, $student) {
                $course = $enrollment->course;
                if (!$course) {
                    return null;
                }

                $completedTopicIds = $completedTopicIdsByUserCourse->get($student->id . ':' . $course->id, []);

                $modules = $course->modules->map(function (Module $module) use ($completedTopicIds) {
                    $topicIds = $module->topics->pluck('id');
                    $total = $topicIds->count();
                    $completed = $topicIds->filter(fn ($id) => in_array($id, $completedTopicIds, true))->count();
                    $pct = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

                    return [
                        'module_id' => $module->id,
                        'module_title' => $module->title,
                        'completed_topics' => $completed,
                        'total_topics' => $total,
                        'progress_percentage' => $pct,
                    ];
                })->values()->all();

                return [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'status' => $enrollment->status,
                    'progress_percentage' => (float) ($enrollment->progress_percentage ?? 0),
                    'modules' => $modules,
                ];
            })->filter()->values();

            $overall = $enrollments->avg('progress_percentage');
            $overall = $overall === null ? 0.0 : (float) $overall;

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
                'state' => $student->state,
                'lga' => $student->lga,
                'avatar' => $student->avatar,
                'occupation' => $student->occupation,
                'gender' => $student->gender,
                'facilitator_id' => $student->facilitator_id,
                'last_active_date' => optional($student->last_active_date)?->toDateString(),
                'overall_progress' => round($overall, 1),
                'overall_band' => self::bandFor($overall),
                'enrollments' => $enrollments->all(),
            ];
        })->values();
    }
}
