<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\Topic;
use App\Models\User;
use App\Support\SmsNudgeSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SmsInactivityNudgeService
{
    public const MAX_NUDGES = 2;

    public function __construct(private readonly SmsService $sms) {}

    /**
     * Send inactivity SMS nudges using the latest admin settings.
     *
     * Cadence (default threshold = 7):
     *  - 1st SMS on day 7 of inactivity (sms_nudge_count 0 → 1)
     *  - 2nd SMS on day 14 if still inactive (sms_nudge_count 1 → 2)
     *
     * @return array{eligible: int, sent: int, skipped_window: bool, skipped_disabled: bool}
     */
    public function run(?bool $forceWindow = false): array
    {
        $settings = SmsNudgeSettings::all();

        if (! ($settings['enabled'] ?? true)) {
            return ['eligible' => 0, 'sent' => 0, 'skipped_window' => false, 'skipped_disabled' => true];
        }

        if (! $forceWindow && ! SmsNudgeSettings::isSendWindowNow($settings['send_time'])) {
            return ['eligible' => 0, 'sent' => 0, 'skipped_window' => true, 'skipped_disabled' => false];
        }

        $thresholdDays = max(1, (int) $settings['inactivity_threshold_days']);
        $learners = $this->eligibleLearners($thresholdDays);
        $sent = 0;

        foreach ($learners as $learner) {
            $context = $this->resumeContext($learner);
            $message = SmsNudgeSettings::render($settings['message_template'], [
                'first_name' => $this->firstName($learner->name),
                'course_title' => $context['course_title'],
                'module_name' => $context['module_name'],
                'last_module_completed' => $context['last_module_completed'],
                'resume_link' => $context['resume_link'],
            ]);

            $keyword = strtoupper((string) $settings['opt_out_keyword']);
            if ($keyword !== '' && ! str_contains(strtoupper($message), $keyword)) {
                $message = rtrim($message).' Reply '.$keyword.' to opt out.';
            }

            if (! $learner->phone) {
                continue;
            }

            $ok = $this->sms->send($learner->phone, $message);
            if ($ok) {
                $learner->forceFill([
                    'sms_nudge_sent_at' => now(),
                    'sms_nudge_count' => min(self::MAX_NUDGES, (int) $learner->sms_nudge_count + 1),
                ])->save();
                $sent++;
            }
        }

        Log::info('SMS inactivity nudges finished', [
            'eligible' => $learners->count(),
            'sent' => $sent,
            'threshold_days' => $thresholdDays,
            'send_time' => $settings['send_time'],
        ]);

        return [
            'eligible' => $learners->count(),
            'sent' => $sent,
            'skipped_window' => false,
            'skipped_disabled' => false,
        ];
    }

    /**
     * Mark a learner as opted out when their inbound SMS matches the configured keyword.
     */
    public function handleInboundOptOut(string $phone, string $body): bool
    {
        $settings = SmsNudgeSettings::all();
        $keyword = strtoupper(trim((string) $settings['opt_out_keyword']));
        $body = strtoupper(trim($body));

        if ($keyword === '' || $body === '' || ($body !== $keyword && ! str_starts_with($body, $keyword.' '))) {
            return false;
        }

        $normalized = $this->sms->normalizePhone($phone);
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';

        $user = User::query()
            ->where('role', 'student')
            ->where(function ($q) use ($phone, $normalized, $digits) {
                $q->where('phone', $phone)
                    ->orWhere('phone', $normalized)
                    ->orWhere('phone', 'like', '%'.substr($digits, -10));
            })
            ->first();

        if (! $user) {
            return false;
        }

        $user->forceFill(['sms_opted_out_at' => now()])->save();

        return true;
    }

    /**
     * Learners due for nudge 1 (inactive ≥ threshold) or nudge 2 (inactive ≥ 2×threshold).
     *
     * @return Collection<int, User>
     */
    public function eligibleLearners(int $thresholdDays): Collection
    {
        $thresholdDays = max(1, $thresholdDays);
        $firstCutoff = Carbon::today()->subDays($thresholdDays);
        $secondCutoff = Carbon::today()->subDays($thresholdDays * 2);
        // Space second send at least ~threshold days after the first.
        $minGapAfterFirst = Carbon::now()->subDays($thresholdDays);

        return User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->whereNull('sms_opted_out_at')
            ->where('sms_nudge_count', '<', self::MAX_NUDGES)
            ->where(function ($q) use ($firstCutoff, $secondCutoff, $minGapAfterFirst) {
                // First nudge: never sent, inactive ≥ threshold days
                $q->where(function ($first) use ($firstCutoff) {
                    $first->where('sms_nudge_count', 0)
                        ->where(function ($inactive) use ($firstCutoff) {
                            $this->applyInactivityFilter($inactive, $firstCutoff);
                        });
                })->orWhere(function ($second) use ($secondCutoff, $minGapAfterFirst) {
                    // Second nudge: one prior send, still inactive ≥ 2×threshold,
                    // and at least threshold days since the first SMS
                    $second->where('sms_nudge_count', 1)
                        ->where(function ($inactive) use ($secondCutoff) {
                            $this->applyInactivityFilter($inactive, $secondCutoff);
                        })
                        ->where(function ($gap) use ($minGapAfterFirst) {
                            $gap->whereNull('sms_nudge_sent_at')
                                ->orWhere('sms_nudge_sent_at', '<=', $minGapAfterFirst);
                        });
                });
            })
            ->limit(500)
            ->get();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\User>  $q
     */
    private function applyInactivityFilter($q, Carbon $cutoff): void
    {
        $q->where(function ($inner) use ($cutoff) {
            $inner->where(function ($a) use ($cutoff) {
                $a->whereNotNull('last_active_date')
                    ->whereDate('last_active_date', '<=', $cutoff);
            })->orWhere(function ($b) use ($cutoff) {
                $b->whereNull('last_active_date')
                    ->whereNotNull('last_login_at')
                    ->whereDate('last_login_at', '<=', $cutoff);
            })->orWhere(function ($c) use ($cutoff) {
                $c->whereNull('last_active_date')
                    ->whereNull('last_login_at')
                    ->whereDate('created_at', '<=', $cutoff);
            });
        });
    }

    /**
     * @return array{
     *     course_title: string,
     *     module_name: string,
     *     last_module_completed: string,
     *     resume_link: string
     * }
     */
    public function resumeContext(User $learner): array
    {
        $frontend = rtrim((string) config('services.frontend.url', config('app.url')), '/');

        $enrollment = Enrollment::query()
            ->with('course:id,title,slug')
            ->where('user_id', $learner->id)
            ->where(function ($q) {
                $q->whereIn('status', ['active', 'enrolled', 'in_progress'])
                    ->orWhereNull('status');
            })
            ->latest('updated_at')
            ->first()
            ?? Enrollment::query()
                ->with('course:id,title,slug')
                ->where('user_id', $learner->id)
                ->latest('updated_at')
                ->first();

        $courseTitle = $enrollment?->course?->title ?: 'your Agrisiti course';
        $courseId = $enrollment?->course_id;

        $moduleName = 'your next module';
        $lastModuleCompleted = 'your last lesson';
        $moduleId = null;

        if ($courseId) {
            $completedTopicIds = StudentProgress::query()
                ->where('user_id', $learner->id)
                ->where('is_completed', true)
                ->pluck('topic_id');

            $lastCompletedTopic = StudentProgress::query()
                ->where('user_id', $learner->id)
                ->where('is_completed', true)
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first()
                ?? StudentProgress::query()
                    ->where('user_id', $learner->id)
                    ->where('is_completed', true)
                    ->latest('updated_at')
                    ->first();

            if ($lastCompletedTopic?->topic_id) {
                $topic = Topic::query()
                    ->with('module:id,title,course_id')
                    ->find($lastCompletedTopic->topic_id);
                if ($topic?->module && (int) $topic->module->course_id === (int) $courseId) {
                    $lastModuleCompleted = $topic->module->title ?: $topic->title;
                } elseif ($topic) {
                    $lastModuleCompleted = $topic->title;
                }
            }

            $module = Module::query()
                ->where('course_id', $courseId)
                ->where('is_active', true)
                ->whereHas('topics', function ($q) use ($completedTopicIds) {
                    if ($completedTopicIds->isEmpty()) {
                        $q->where('is_active', true);
                    } else {
                        $q->where('is_active', true)->whereNotIn('id', $completedTopicIds);
                    }
                })
                ->orderBy('sort_order')
                ->first()
                ?? Module::query()
                    ->where('course_id', $courseId)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();

            if ($module) {
                $moduleName = $module->title;
                $moduleId = $module->id;
            }
        }

        // Deep link: /courses/{id}/module/{id} — frontend prompts login then opens the module.
        $resumeLink = ($courseId && $moduleId)
            ? "{$frontend}/courses/{$courseId}/module/{$moduleId}"
            : ($courseId
                ? "{$frontend}/courses/{$courseId}/module/0"
                : "{$frontend}/my-courses");

        return [
            'course_title' => $courseTitle,
            'module_name' => $moduleName,
            'last_module_completed' => $lastModuleCompleted,
            'resume_link' => $resumeLink,
        ];
    }

    private function firstName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'there';
        }

        return explode(' ', $name)[0];
    }
}
