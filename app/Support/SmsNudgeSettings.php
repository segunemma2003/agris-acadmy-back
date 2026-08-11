<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Admin-configurable SMS inactivity nudge campaign settings.
 * Stored in the settings table under key "sms_nudge".
 */
class SmsNudgeSettings
{
    public const KEY = 'sms_nudge';

    public const MERGE_TAGS = [
        '{{first_name}}',
        '{{course_title}}',
        '{{module_name}}',
        '{{resume_link}}',
    ];

    public const SEND_TIMES = [
        'morning' => 'Morning (08:00)',
        'afternoon' => 'Afternoon (13:00)',
        'evening' => 'Evening (18:00)',
    ];

    /**
     * @return array{
     *     enabled: bool,
     *     inactivity_threshold_days: int,
     *     message_template: string,
     *     send_time: string,
     *     opt_out_keyword: string
     * }
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'inactivity_threshold_days' => 7,
            'message_template' => 'Hi {{first_name}}, you still have {{module_name}} waiting in {{course_title}}. Resume here: {{resume_link}}. Reply STOP to opt out.',
            'send_time' => 'morning',
            'opt_out_keyword' => 'STOP',
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     inactivity_threshold_days: int,
     *     message_template: string,
     *     send_time: string,
     *     opt_out_keyword: string
     * }
     */
    public static function all(): array
    {
        $stored = Setting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge(self::defaults(), $stored);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): array
    {
        $defaults = self::defaults();

        $payload = [
            'enabled' => (bool) ($data['enabled'] ?? $defaults['enabled']),
            'inactivity_threshold_days' => max(1, (int) ($data['inactivity_threshold_days'] ?? $defaults['inactivity_threshold_days'])),
            'message_template' => trim((string) ($data['message_template'] ?? $defaults['message_template'])),
            'send_time' => in_array(($data['send_time'] ?? ''), array_keys(self::SEND_TIMES), true)
                ? $data['send_time']
                : $defaults['send_time'],
            'opt_out_keyword' => strtoupper(trim((string) ($data['opt_out_keyword'] ?? $defaults['opt_out_keyword']))) ?: 'STOP',
        ];

        Setting::putValue(self::KEY, $payload);

        return $payload;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public static function render(string $template, array $replacements): string
    {
        $map = [];
        foreach ($replacements as $key => $value) {
            $tag = str_starts_with($key, '{{') ? $key : '{{'.$key.'}}';
            $map[$tag] = (string) $value;
        }

        return strtr($template, $map);
    }

    /**
     * Sample values for admin preview before save.
     *
     * @return array<string, string>
     */
    public static function previewReplacements(): array
    {
        $frontend = rtrim((string) config('services.frontend.url', config('app.url')), '/');

        return [
            'first_name' => 'Ada',
            'course_title' => 'Rice Value Chain Essentials',
            'module_name' => 'Module 2: Nursery Management',
            'resume_link' => $frontend.'/courses/rice-value-chain-essentials',
        ];
    }

    public static function isSendWindowNow(?string $sendTime = null): bool
    {
        $sendTime ??= self::all()['send_time'];
        $hour = (int) now()->format('G');

        return match ($sendTime) {
            'morning' => $hour >= 7 && $hour < 11,
            'afternoon' => $hour >= 12 && $hour < 16,
            'evening' => $hour >= 17 && $hour < 21,
            default => false,
        };
    }

    public static function scheduleHour(string $sendTime): string
    {
        return match ($sendTime) {
            'afternoon' => '13:00',
            'evening' => '18:00',
            default => '08:00',
        };
    }
}
