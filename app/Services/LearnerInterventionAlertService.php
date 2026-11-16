<?php

namespace App\Services;

use App\Mail\LearnerInterventionAlertMail;
use App\Models\Enrollment;
use App\Models\LearnerInterventionAlert;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use App\Models\User;
use App\Support\SmsNudgeSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LearnerInterventionAlertService
{
    public const ADMIN_EMAIL = 'admin@agrisiti.com';

    public const INACTIVE_DAYS = 7;

    /** Don't re-email the same alert context more often than this. */
    public const REEMAIL_AFTER_DAYS = 7;

    /** In a course (some progress, not finished) but no lesson activity for this long. */
    public const STALLED_DAYS = 14;

    /** Enrolled this long ago with no lesson opened yet. */
    public const NEVER_STARTED_DAYS = 7;

    public function inactivityThresholdDays(): int
    {
        return max(1, (int) (SmsNudgeSettings::all()['inactivity_threshold_days'] ?? self::INACTIVE_DAYS));
    }

    /**
     * Detect at-risk learners, persist new/updated alert rows, and email admin
     * a digest of alerts that are due to be sent.
     *
     * Alerts that no longer apply (the learner re-engaged) are auto-resolved;
     * alerts that come back are reopened (a staff resolution holds for
     * REEMAIL_AFTER_DAYS first).
     *
     * @return array{detected: int, emailed: int, resolved: int}
     */
    public function runNightly(): array
    {
        // Plain collections: an empty Eloquent collection can't merge arrays
        $candidates = collect()
            ->merge($this->detectInactiveLearners()->toBase())
            ->merge($this->detectQuizFailures()->toBase())
            ->merge($this->detectStalledCourses()->toBase())
            ->merge($this->detectNeverStarted()->toBase())
            ->values();

        $toEmail = collect();
        $seenIds = [];

        foreach ($candidates as $candidate) {
            $alert = LearnerInterventionAlert::updateOrCreate(
                [
                    'user_id' => $candidate['user_id'],
                    'reason' => $candidate['reason'],
                    'context_key' => $candidate['context_key'],
                ],
                [
                    'learner_name' => $candidate['learner_name'],
                    'last_login_at' => $candidate['last_login_at'],
                    'stuck_label' => $candidate['stuck_label'],
                    'facilitator_id' => $candidate['facilitator_id'],
                    'payload' => $candidate['payload'],
                ]
            );
            $seenIds[] = $alert->id;

            if ($alert->resolved_at) {
                $reopen = $alert->auto_resolved
                    || $alert->resolved_at->lte(now()->subDays(self::REEMAIL_AFTER_DAYS));
                if (! $reopen) {
                    continue; // handled by staff recently — leave it closed for now
                }
                $alert->forceFill([
                    'resolved_at' => null, 'resolved_by' => null, 'auto_resolved' => false,
                    'resolution_note' => null, 'emailed_at' => null,
                ])->save();
            }

            $shouldEmail = $alert->emailed_at === null
                || $alert->emailed_at->lte(now()->subDays(self::REEMAIL_AFTER_DAYS));

            if ($shouldEmail) {
                $toEmail->push($alert);
            }
        }

        // Open alerts not detected tonight: the learner is back on track.
        $resolved = LearnerInterventionAlert::open()
            ->whereNotIn('id', $seenIds ?: [0])
            ->update([
                'resolved_at' => now(),
                'auto_resolved' => true,
                'resolution_note' => 'Auto-resolved: learner no longer meets this risk condition.',
            ]);

        if ($toEmail->isEmpty()) {
            return ['detected' => $candidates->count(), 'emailed' => 0, 'resolved' => $resolved];
        }

        try {
            Mail::to(self::ADMIN_EMAIL)->send(new LearnerInterventionAlertMail($toEmail));

            $now = now();
            LearnerInterventionAlert::whereIn('id', $toEmail->pluck('id'))
                ->update(['emailed_at' => $now]);
        } catch (\Throwable $e) {
            Log::error('Learner intervention alert email failed', [
                'error' => $e->getMessage(),
                'count' => $toEmail->count(),
            ]);

            throw $e;
        }

        return ['detected' => $candidates->count(), 'emailed' => $toEmail->count(), 'resolved' => $resolved];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function detectInactiveLearners(): Collection
    {
        $thresholdDays = $this->inactivityThresholdDays();
        $cutoff = Carbon::today()->subDays($thresholdDays);

        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('last_active_date')
                        ->whereDate('last_active_date', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('last_active_date')
                        ->whereNotNull('last_login_at')
                        ->whereDate('last_login_at', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    // Never logged in / active but created more than threshold days ago
                    $inner->whereNull('last_active_date')
                        ->whereNull('last_login_at')
                        ->whereDate('created_at', '<=', $cutoff);
                });
            })
            ->get();

        return $students->map(function (User $student) use ($thresholdDays) {
            $stuck = $this->stuckModuleLabel($student);

            return [
                'user_id' => $student->id,
                'reason' => 'inactive_7d',
                'context_key' => 'inactive_'.$thresholdDays.'d',
                'learner_name' => $student->name,
                'last_login_at' => $student->last_login_at,
                'stuck_label' => $stuck,
                'facilitator_id' => $student->facilitator_id,
                'payload' => [
                    'last_active_date' => optional($student->last_active_date)?->toDateString(),
                    'email' => $student->email,
                    'state' => $student->state,
                    'lga' => $student->lga,
                    'inactive_days_threshold' => $thresholdDays,
                ],
            ];
        });
    }

    /**
     * Learners who failed the same quiz at least twice and have never passed it.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function detectQuizFailures(): Collection
    {
        $alerts = collect();

        $modulePairs = TestAttempt::query()
            ->where('is_passed', false)
            ->whereNotNull('completed_at')
            ->get(['user_id', 'module_test_id'])
            ->groupBy(fn ($row) => $row->user_id . ':' . $row->module_test_id)
            ->filter(fn ($rows) => $rows->count() >= 2);

        foreach ($modulePairs as $key => $rows) {
            [$userId, $testId] = array_map('intval', explode(':', $key));

            $hasPassed = TestAttempt::where('user_id', $userId)
                ->where('module_test_id', $testId)
                ->where('is_passed', true)
                ->exists();

            if ($hasPassed) {
                continue;
            }

            $user = User::where('id', $userId)->where('role', 'student')->where('is_active', true)->first();
            if (!$user) {
                continue;
            }

            $attempt = TestAttempt::with(['moduleTest.module', 'moduleTest.course'])
                ->where('user_id', $userId)
                ->where('module_test_id', $testId)
                ->latest('completed_at')
                ->first();

            $test = $attempt?->moduleTest;
            $label = $test
                ? trim(($test->course?->title ? $test->course->title . ' — ' : '') . ($test->module?->title ? $test->module->title . ' / ' : '') . $test->title)
                : "Module quiz #{$testId}";

            $alerts->push([
                'user_id' => $user->id,
                'reason' => 'quiz_failed_twice',
                'context_key' => "module_test:{$testId}",
                'learner_name' => $user->name,
                'last_login_at' => $user->last_login_at,
                'stuck_label' => $label,
                'facilitator_id' => $user->facilitator_id,
                'payload' => [
                    'quiz_type' => 'module_test',
                    'quiz_id' => $testId,
                    'fail_count' => $rows->count(),
                    'email' => $user->email,
                ],
            ]);
        }

        $topicPairs = TopicTestAttempt::query()
            ->where('is_passed', false)
            ->whereNotNull('completed_at')
            ->get(['user_id', 'topic_test_id'])
            ->groupBy(fn ($row) => $row->user_id . ':' . $row->topic_test_id)
            ->filter(fn ($rows) => $rows->count() >= 2);

        foreach ($topicPairs as $key => $rows) {
            [$userId, $testId] = array_map('intval', explode(':', $key));

            $hasPassed = TopicTestAttempt::where('user_id', $userId)
                ->where('topic_test_id', $testId)
                ->where('is_passed', true)
                ->exists();

            if ($hasPassed) {
                continue;
            }

            $user = User::where('id', $userId)->where('role', 'student')->where('is_active', true)->first();
            if (!$user) {
                continue;
            }

            $attempt = TopicTestAttempt::with(['topicTest.topic', 'topicTest.module', 'topicTest.course'])
                ->where('user_id', $userId)
                ->where('topic_test_id', $testId)
                ->latest('completed_at')
                ->first();

            $test = $attempt?->topicTest;
            $label = $test
                ? trim(($test->course?->title ? $test->course->title . ' — ' : '') . ($test->module?->title ? $test->module->title . ' / ' : '') . $test->title)
                : "Topic quiz #{$testId}";

            $alerts->push([
                'user_id' => $user->id,
                'reason' => 'quiz_failed_twice',
                'context_key' => "topic_test:{$testId}",
                'learner_name' => $user->name,
                'last_login_at' => $user->last_login_at,
                'stuck_label' => $label,
                'facilitator_id' => $user->facilitator_id,
                'payload' => [
                    'quiz_type' => 'topic_test',
                    'quiz_id' => $testId,
                    'fail_count' => $rows->count(),
                    'email' => $user->email,
                ],
            ]);
        }

        return $alerts->values();
    }

    /**
     * Learners partway through a course (some progress, not finished) with no
     * lesson activity in that course for STALLED_DAYS — losing interest.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function detectStalledCourses(): Collection
    {
        $cutoff = now()->subDays(self::STALLED_DAYS);

        $lastActivity = StudentProgress::query()
            ->selectRaw('user_id, course_id, MAX(COALESCE(last_accessed_at, updated_at)) as last_at')
            ->groupBy('user_id', 'course_id')
            ->get()
            ->keyBy(fn ($row) => $row->user_id . ':' . $row->course_id);

        return Enrollment::query()
            ->where('status', 'active')
            ->where('progress_percentage', '>', 0)
            ->where('progress_percentage', '<', 100)
            ->whereHas('user', fn ($q) => $q->where('role', 'student')->where('is_active', true))
            ->with(['user', 'course:id,title'])
            ->get()
            ->filter(function (Enrollment $e) use ($lastActivity, $cutoff) {
                $row = $lastActivity->get($e->user_id . ':' . $e->course_id);
                $last = $row ? Carbon::parse($row->last_at) : ($e->enrolled_at ?? $e->created_at);
                return $last && $last->lte($cutoff);
            })
            ->map(function (Enrollment $e) use ($lastActivity) {
                $row = $lastActivity->get($e->user_id . ':' . $e->course_id);
                return $this->candidate($e->user, 'stalled_course', "course:{$e->course_id}",
                    ($e->course?->title ?? 'Course') . ' — ' . round((float) $e->progress_percentage) . '% done', [
                        'course_id' => $e->course_id,
                        'progress_percentage' => (float) $e->progress_percentage,
                        'last_lesson_activity' => $row ? Carbon::parse($row->last_at)->toDateString() : null,
                        'stalled_days_threshold' => self::STALLED_DAYS,
                    ]);
            })
            ->values();
    }

    /**
     * Learners who enrolled NEVER_STARTED_DAYS+ ago and haven't opened a single
     * lesson in that course.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function detectNeverStarted(): Collection
    {
        $cutoff = now()->subDays(self::NEVER_STARTED_DAYS);
        $started = StudentProgress::query()->select('user_id', 'course_id')->distinct()->get()
            ->map(fn ($row) => $row->user_id . ':' . $row->course_id)->flip();

        return Enrollment::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('progress_percentage')->orWhere('progress_percentage', 0))
            ->where(fn ($q) => $q->where('enrolled_at', '<=', $cutoff)
                ->orWhere(fn ($q) => $q->whereNull('enrolled_at')->where('created_at', '<=', $cutoff)))
            ->whereHas('user', fn ($q) => $q->where('role', 'student')->where('is_active', true))
            ->with(['user', 'course:id,title'])
            ->get()
            ->reject(fn (Enrollment $e) => $started->has($e->user_id . ':' . $e->course_id))
            ->map(fn (Enrollment $e) => $this->candidate($e->user, 'never_started', "course:{$e->course_id}",
                $e->course?->title ?? 'Course', [
                    'course_id' => $e->course_id,
                    'enrolled_at' => optional($e->enrolled_at ?? $e->created_at)->toDateString(),
                    'never_started_days_threshold' => self::NEVER_STARTED_DAYS,
                ]))
            ->values();
    }

    /** One alert candidate row. */
    private function candidate(User $user, string $reason, string $contextKey, string $label, array $payload): array
    {
        return [
            'user_id' => $user->id,
            'reason' => $reason,
            'context_key' => $contextKey,
            'learner_name' => $user->name,
            'last_login_at' => $user->last_login_at,
            'stuck_label' => $label,
            'facilitator_id' => $user->facilitator_id,
            'payload' => $payload + ['email' => $user->email, 'state' => $user->state, 'lga' => $user->lga],
        ];
    }

    private function stuckModuleLabel(User $student): string
    {
        $enrollment = $student->enrollments()
            ->whereIn('status', ['active', 'completed'])
            ->with(['course.modules' => fn ($q) => $q->where('is_active', true)->with('topics:id,module_id')])
            ->orderByDesc('updated_at')
            ->first();

        if (!$enrollment?->course) {
            return 'No active course / module';
        }

        $completedTopicIds = StudentProgress::where('user_id', $student->id)
            ->where('course_id', $enrollment->course_id)
            ->where('is_completed', true)
            ->pluck('topic_id')
            ->all();

        /** @var Module|null $stuckModule */
        $stuckModule = $enrollment->course->modules->first(function (Module $module) use ($completedTopicIds) {
            $topicIds = $module->topics->pluck('id');
            if ($topicIds->isEmpty()) {
                return false;
            }

            return $topicIds->contains(fn ($id) => !in_array($id, $completedTopicIds, true));
        });

        if (!$stuckModule) {
            return $enrollment->course->title . ' (all modules complete or empty)';
        }

        return $enrollment->course->title . ' — ' . $stuckModule->title;
    }
}
