<?php

namespace App\Support;

/**
 * CV templates that follow local hiring conventions. Each template decides
 * which personal details appear, how references are handled, and the order
 * of sections, in the web view and the PDF alike.
 */
class CvTemplates
{
    public const DEFAULT = 'nigeria';

    /** Section keys a template can order. */
    public const SECTIONS = ['summary', 'experience', 'training', 'education', 'skills', 'languages', 'achievements', 'certificates', 'interests', 'references'];

    public const ALL = [
        'nigeria' => [
            'label' => 'Nigeria (standard CV)',
            'description' => 'Personal details (date of birth, gender, nationality, state), career objective and referees listed in full: what most Nigerian employers expect.',
            'countries' => ['Nigeria'],
            'photo' => true,
            'personal' => ['date_of_birth', 'gender', 'nationality', 'state_of_origin'],
            'references' => 'list',
            'summary_title' => 'Career objective',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'languages', 'achievements', 'certificates', 'interests', 'references'],
            'color' => [27, 127, 110],
        ],
        'west_africa' => [
            'label' => 'West & East Africa',
            'description' => 'Like the Nigerian CV, with nationality and referees, suited to Ghana, Kenya, Rwanda, Cameroon and neighbours.',
            'countries' => ['Ghana', 'Kenya', 'Rwanda', 'Cameroon', 'Senegal', "Côte d'Ivoire", 'Uganda', 'Tanzania', 'Sierra Leone', 'Liberia', 'Gambia'],
            'photo' => true,
            'personal' => ['nationality', 'date_of_birth'],
            'references' => 'list',
            'summary_title' => 'Profile',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'languages', 'certificates', 'achievements', 'references'],
            'color' => [27, 127, 110],
        ],
        'uk' => [
            'label' => 'United Kingdom CV',
            'description' => 'No photo, date of birth or gender (UK equality law). Personal statement first; references available on request.',
            'countries' => ['United Kingdom', 'Ireland'],
            'photo' => false,
            'personal' => [],
            'references' => 'on_request',
            'summary_title' => 'Personal statement',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'achievements', 'certificates', 'languages', 'references'],
            'color' => [31, 41, 55],
        ],
        'us' => [
            'label' => 'US / Canada résumé',
            'description' => 'Short and results-focused. No photo, age, gender or references; skills near the top.',
            'countries' => ['United States', 'Canada'],
            'photo' => false,
            'personal' => [],
            'references' => 'none',
            'summary_title' => 'Summary',
            'order' => ['summary', 'skills', 'experience', 'training', 'education', 'achievements', 'certificates', 'languages'],
            'color' => [30, 64, 175],
        ],
        'europass' => [
            'label' => 'Europass (EU)',
            'description' => 'The European format: nationality, languages with CEFR levels (A1–C2), and digital skills.',
            'countries' => ['Netherlands', 'Germany', 'France', 'Belgium', 'Spain', 'Italy', 'Portugal', 'Poland', 'Sweden', 'Denmark', 'Austria', 'Finland'],
            'photo' => false,
            'personal' => ['nationality'],
            'references' => 'on_request',
            'summary_title' => 'About me',
            'order' => ['summary', 'experience', 'training', 'education', 'languages', 'skills', 'certificates', 'achievements', 'references'],
            'color' => [0, 51, 153],
        ],
        'gulf' => [
            'label' => 'Gulf (UAE, Saudi, Qatar)',
            'description' => 'Photo, nationality, date of birth and gender are expected; languages and references included.',
            'countries' => ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain'],
            'photo' => true,
            'personal' => ['nationality', 'date_of_birth', 'gender'],
            'references' => 'list',
            'summary_title' => 'Career objective',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'languages', 'certificates', 'achievements', 'references'],
            'color' => [120, 53, 15],
        ],
    ];

    public static function get(?string $key): array
    {
        return self::ALL[$key] ?? self::ALL[self::DEFAULT];
    }

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /** Best template for a target country (falls back to the Nigerian CV). */
    public static function forCountry(?string $country): string
    {
        foreach (self::ALL as $key => $t) {
            foreach ($t['countries'] as $c) {
                if ($country && strcasecmp(trim($country), $c) === 0) {
                    return $key;
                }
            }
        }

        return self::DEFAULT;
    }

    /** Public list for the template picker. */
    public static function catalogue(): array
    {
        return collect(self::ALL)->map(fn ($t, $key) => [
            'key' => $key,
            'label' => $t['label'],
            'description' => $t['description'],
            'countries' => $t['countries'],
            'photo' => $t['photo'],
            'personal' => $t['personal'],
            'references' => $t['references'],
            'summary_title' => $t['summary_title'],
            'order' => $t['order'],
        ])->values()->all();
    }
}
