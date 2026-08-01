<?php

namespace App\Support;

/**
 * Default notification preference shape for learners.
 * Channels: in_app | email. Types cover LMS + career events.
 */
class NotificationPreferences
{
    public const TYPES = [
        'forum_reply',
        'certificate_ready',
        'course_completed',
        'module_added',
        'message_sent',
        'assignment_graded',
        'apprenticeship_reviewed',
        'enrollment_confirmed',
        'apprenticeship_interest',
    ];

    public static function defaults(): array
    {
        $inApp = [];
        $email = [];

        foreach (self::TYPES as $type) {
            $inApp[$type] = true;
            // Email defaults: important milestones on; chatter off.
            $email[$type] = in_array($type, [
                'certificate_ready',
                'course_completed',
                'assignment_graded',
                'apprenticeship_reviewed',
                'message_sent',
            ], true);
        }

        return [
            'in_app' => $inApp,
            'email' => $email,
        ];
    }

    public static function merge(?array $stored): array
    {
        $defaults = self::defaults();

        if (!$stored) {
            return $defaults;
        }

        foreach (['in_app', 'email'] as $channel) {
            $defaults[$channel] = array_merge(
                $defaults[$channel],
                is_array($stored[$channel] ?? null) ? $stored[$channel] : []
            );
        }

        return $defaults;
    }

    public static function allowsInApp(?array $stored, string $type): bool
    {
        $prefs = self::merge($stored);

        return (bool) ($prefs['in_app'][$type] ?? true);
    }

    public static function allowsEmail(?array $stored, string $type): bool
    {
        $prefs = self::merge($stored);

        return (bool) ($prefs['email'][$type] ?? false);
    }
}
