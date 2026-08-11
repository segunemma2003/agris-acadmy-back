<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\User;
use App\Support\SmsNudgeSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SmsInactivityNudgeService
{
    public function __construct(private readonly SmsService $sms) {}

    /**
     * Send inactivity SMS nudges using the latest admin settings.
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

        $learners = $this->eligibleLearners((int) $settings['inactivity_threshold_days']);
        $sent = 0;

        foreach ($learners as $learner) {
            $context = $this->resumeContext($learner);
            $message = SmsNudgeSettings::render($settings['message_template'], [
                'first_name' => $this->firstName($learner->name),
                'course_title' => $context['course_title'],
                'module_name' => $context['module_name'],
                'resume_link' => $context['resume_link'],
            ]);

            // Append opt-out hint if template doesn't already include the keyword.
            $keyword = strtoupper((string) $settings['opt_out_keyword']);
            if ($keyword !== '' && ! str_contains(strtoupper($message), $keyword)) {
                $message = rtrim($message).' Reply '.$keyword.' to opt out.';
            }

            if (! $learner->phone) {
                continue;
            }

            $ok = $this->sms->send($learner->phone, $message);
            if ($ok) {
                $learner->forceFill(['sms_nudge_sent_at' => now()])->save();
                $sent++;
            }
        }

        Log::info('SMS inactivity nudges finished', [
            'eligible' => $learners->count(),
            'sent' => $sent,
            'threshold_days' => $settings['inactivity_threshold_days'],
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
     * @return Collection<int, User>
     */
    public function eligibleLearners(int $thresholdDays): Collection
    {
        $cutoff = Carbon::today()->subDays(max(1, $thresholdDays));
        $cooldown = Carbon::now()->subDays(max(1, $thresholdDays));

        return User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->whereNull('sms_opted_out_at')
            ->where(function ($q) use ($cooldown) {
                $q->whereNull('sms_nudge_sent_at')
                    ->orWhere('sms_nudge_sent_at', '<=', $cooldown);
            })
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('last_active_date')
                        ->whereDate('last_active_date', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('last_active_date')
                        ->whereNotNull('last_login_at')
                        ->whereDate('last_login_at', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('last_active_date')
                        ->whereNull('last_login_at')
                        ->whereDate('created_at', '<=', $cutoff);
                });
            })
            ->limit(500)
            ->get();
    }

    /**
     * @return array{course_title: string, module_name: string, resume_link: string}
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
        $courseSlug = $enrollment?->course?->slug;
        $courseId = $enrollment?->course_id;

        $moduleName = 'your next module';
        if ($courseId) {
            $completedTopicIds = StudentProgress::query()
                ->where('user_id', $learner->id)
                ->where('is_completed', true)
                ->pluck('topic_id');

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
            }
        }

        $resumeLink = $courseSlug
            ? "{$frontend}/courses/{$courseSlug}"
            : ($courseId ? "{$frontend}/courses/{$courseId}" : "{$frontend}/my-courses");

        return [
            'course_title' => $courseTitle,
            'module_name' => $moduleName,
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
