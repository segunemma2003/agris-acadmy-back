<?php

namespace App\Support;

/** Languages learners can switch lesson captions to. English comes from YouTube; the rest are translated. */
final class CaptionLanguages
{
    public const SOURCE = 'en';

    public const ALL = [
        'en' => 'English',
        'ha' => 'Hausa',
        'yo' => 'Yorùbá',
        'ig' => 'Igbo',
        'fr' => 'Français',
        'pt' => 'Português',
        'es' => 'Español',
    ];

    /** English names, used when asking Claude to translate. */
    public const ENGLISH_NAMES = [
        'en' => 'English', 'ha' => 'Hausa', 'yo' => 'Yoruba', 'ig' => 'Igbo',
        'fr' => 'French', 'pt' => 'Portuguese', 'es' => 'Spanish',
    ];

    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    public static function targets(): array
    {
        return array_values(array_diff(self::codes(), [self::SOURCE]));
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::ALL);
    }

    /** YouTube video id from any watch / share / embed URL. */
    public static function youtubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|v/|u/\w/|embed/|shorts/|watch\?v=|&v=)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
