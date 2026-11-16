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

    /**
     * Conventions checked against current guidance (Jobberman Nigeria, UK
     * National Careers Service, Europass, US résumé and Gulf recruiter guides).
     */
    public const ALL = [
        'nigeria' => [
            'label' => 'Nigeria (private sector)',
            'description' => 'The modern Nigerian CV: contact details and location only (no date of birth, state of origin, marital status or photo unless the advert asks), a professional summary, and 2–3 referees with full contact details.',
            'countries' => ['Nigeria'],
            'photo' => false,
            'personal' => [],
            'references' => 'list',
            'summary_title' => 'Professional summary',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'certificates', 'languages', 'achievements', 'interests', 'references'],
            'color' => [27, 127, 110],
        ],
        'nigeria_public' => [
            'label' => 'Nigeria (government / academic)',
            'description' => 'For civil service, university and NGO forms that still ask for date of birth, sex, marital status, state of origin and LGA, with a career objective and referees.',
            'countries' => [],
            'photo' => false,
            'personal' => ['date_of_birth', 'gender', 'marital_status', 'nationality', 'state_of_origin', 'lga'],
            'references' => 'list',
            'summary_title' => 'Career objective',
            'order' => ['summary', 'education', 'experience', 'training', 'certificates', 'skills', 'languages', 'achievements', 'interests', 'references'],
            'color' => [21, 94, 63],
        ],
        'west_africa' => [
            'label' => 'West & East Africa',
            'description' => 'Ghana, Kenya, Rwanda, Cameroon and neighbours: contact details and nationality, professional summary, and referees listed with contact details.',
            'countries' => ['Ghana', 'Kenya', 'Rwanda', 'Cameroon', 'Senegal', "Côte d'Ivoire", 'Uganda', 'Tanzania', 'Sierra Leone', 'Liberia', 'Gambia'],
            'photo' => false,
            'personal' => ['nationality'],
            'references' => 'list',
            'summary_title' => 'Professional summary',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'languages', 'certificates', 'achievements', 'references'],
            'color' => [27, 127, 110],
        ],
        'uk' => [
            'label' => 'United Kingdom CV',
            'description' => 'No photo, date of birth, gender or nationality (UK equality practice). Personal statement first; "References available on request".',
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
            'description' => 'Short and results-focused: summary, experience, education, skills. No photo, age, gender, nationality or references.',
            'countries' => ['United States', 'Canada'],
            'photo' => false,
            'personal' => [],
            'references' => 'none',
            'summary_title' => 'Summary',
            'order' => ['summary', 'experience', 'training', 'education', 'skills', 'achievements', 'certificates', 'languages'],
            'color' => [30, 64, 175],
        ],
        'europass' => [
            'label' => 'Europass (EU)',
            'description' => 'The European format: personal information with nationality, "About me", work experience, education and training, languages on the CEFR scale (A1–C2), and digital and other skills.',
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
            'description' => 'Professional photo, nationality and visa status up front (recruiters filter on these), date of birth optional; summary, experience, education, languages and references.',
            'countries' => ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain'],
            'photo' => true,
            'personal' => ['nationality', 'visa_status', 'date_of_birth'],
            'references' => 'list',
            'summary_title' => 'Professional summary',
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
