<?php

namespace App\Services;

use App\Mail\LearnerInterventionAlertMail;
use App\Models\LearnerInterventionAlert;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use App\Models\User;
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

    /**
     * Detect at-risk learners, persist new/updated alert rows, and email admin
     * a digest of alerts that are due to be sent.
     *
     * @return array{detected: int, emailed: int}
     */
    public function runNightly(): array
    {
        $candidates = $this->detectInactiveLearners()
            ->merge($this->detectQuizFailures())
            ->values();

        $toEmail = collect();

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

            $shouldEmail = $alert->emailed_at === null
                || $alert->emailed_at->lte(now()->subDays(self::REEMAIL_AFTER_DAYS));

            if ($shouldEmail) {
                $toEmail->push($alert);
            }
        }

        if ($toEmail->isEmpty()) {
            return ['detected' => $candidates->count(), 'emailed' => 0];
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

        return ['detected' => $candidates->count(), 'emailed' => $toEmail->count()];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function detectInactiveLearners(): Collection
    {
        $cutoff = Carbon::today()->subDays(self::INACTIVE_DAYS);

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
                    // Never logged in / active but created more than 7 days ago
                    $inner->whereNull('last_active_date')
                        ->whereNull('last_login_at')
                        ->whereDate('created_at', '<=', $cutoff);
                });
            })
            ->get();

        return $students->map(function (User $student) {
            $stuck = $this->stuckModuleLabel($student);

            return [
                'user_id' => $student->id,
                'reason' => 'inactive_7d',
                'context_key' => 'inactive_7d',
                'learner_name' => $student->name,
                'last_login_at' => $student->last_login_at,
                'stuck_label' => $stuck,
                'facilitator_id' => $student->facilitator_id,
                'payload' => [
                    'last_active_date' => optional($student->last_active_date)?->toDateString(),
                    'email' => $student->email,
                    'state' => $student->state,
                    'lga' => $student->lga,
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
