<?php

namespace App\Support;

/**
 * Answer options for the participant registration form (Parts 1–4) and the
 * end-of-course check (Part 5). One source for API validation and the admin
 * panel's labels; the learner app shows translated labels for the same values.
 */
class ParticipantProfile
{
    public const CONSENT_VERSION = 'October 2026';

    public const NATIONALITY = ['nigerian' => 'Nigerian', 'other' => 'Other nationality'];

    public const YES_NO_PREFER = ['yes' => 'Yes', 'no' => 'No', 'prefer_not_to_say' => 'Prefer not to say'];

    public const DISABILITY_TYPES = [
        'seeing' => 'Seeing',
        'self_care' => 'Self-care (washing, dressing)',
        'hearing' => 'Hearing',
        'communicating' => 'Communicating',
        'walking' => 'Walking or climbing',
        'remembering' => 'Remembering or concentrating',
        'other' => 'Other',
    ];

    public const DISPLACEMENT = [
        'none' => 'None of these',
        'idp' => 'Internally displaced person (IDP)',
        'refugee' => 'Refugee',
        'host_community' => 'Host community (living with displaced people)',
        'prefer_not_to_say' => 'Prefer not to say',
    ];

    public const WOMEN_CATEGORY = [
        'young_no_children' => 'Young woman, no children',
        'young_mother' => 'Young mother',
        'head_of_household' => 'Woman head of household',
        'farmer' => 'Woman farmer or producer',
        'entrepreneur' => 'Woman entrepreneur or trader',
        'other' => 'Other',
    ];

    public const EDUCATION = [
        'no_formal' => 'No formal education',
        'primary' => 'Primary',
        'secondary' => 'Secondary (WAEC, NECO)',
        'vocational' => 'Vocational or technical (TVET, NABTEB, trade test)',
        'diploma' => 'Diploma (OND, NCE)',
        'degree' => 'Degree or HND',
        'postgraduate' => 'Postgraduate',
    ];

    public const LOCALITY = [
        'rural' => 'Rural (village)',
        'peri_urban' => 'Peri-urban (edge of town)',
        'urban' => 'Urban (town or city)',
    ];

    /** 3.1 Which best describes you? (users.learner_category — `occupation` is the chatbot's course-matching track) */
    public const LEARNER_CATEGORY = [
        'student' => 'Student',
        'job_seeker' => 'Job seeker',
        'farmer' => 'Farmer',
        'apprentice' => 'Apprentice, intern or NYSC',
        'employee' => 'Employee',
        'entrepreneur' => 'Entrepreneur or business owner',
        'other' => 'Other',
    ];

    /** 3.2 What is your main work now? */
    public const MAIN_WORK = [
        'employed' => 'Working for someone and paid',
        'self_employed' => 'Running my own farm or business',
        'unpaid_family' => 'Helping family farm or business, unpaid',
        'apprentice' => 'Apprentice, intern or NYSC',
        'student' => 'Student',
        'looking' => 'Looking for work',
        'not_working' => 'Not working, not looking',
    ];

    /** MAIN_WORK values that count as "working" (questions 3.3–3.6 apply). */
    public const WORKING = ['employed', 'self_employed', 'unpaid_family', 'apprentice'];

    public const EMPLOYMENT_TYPE = ['full_time' => 'Full time', 'part_time' => 'Part time', 'seasonal' => 'Seasonal or casual'];

    public const ORGANISATIONS = [
        'Kano Agricultural Supply Company Limited - KASCO',
        'The Agro-Climatic Resilience in Semi-Arid Landscapes (ACReSAL) - Kano',
        'Nigeria Climate Adaptation Erosion and Watershed - Kano',
        'Kano State Agricultural and Rural Development Authority (KNARDA)',
        'Anadariya Farms Kano',
        'Kwanar Dawaki rice Processing and marketing Association',
        'Darussalam Global Farms Limited',
        'ITF Minna',
        'Niger Foods Minna',
    ];

    public const SECTOR = [
        'primary' => 'Primary (farming, fishing, livestock)',
        'secondary' => 'Secondary (processing, packaging)',
        'tertiary' => 'Tertiary (trade, transport, services, inputs)',
    ];

    public const COMMODITY = [
        'rice' => 'Rice', 'poultry' => 'Poultry', 'maize' => 'Maize', 'fish' => 'Fish',
        'soybean' => 'Soybean', 'livestock' => 'Goats, sheep or cattle', 'cassava' => 'Cassava',
        'inputs' => 'Inputs (seed, feed, fertiliser)', 'vegetables' => 'Vegetables', 'fruits' => 'Fruits',
        'other' => 'Other',
    ];

    public const INCOME = [
        'none' => 'No income',
        'under_30000' => 'Under ₦30,000',
        '30000_70000' => '₦30,000 to ₦70,000',
        '70001_150000' => '₦70,001 to ₦150,000',
        '150001_300000' => '₦150,001 to ₦300,000',
        'above_300000' => 'Above ₦300,000',
        'prefer_not_to_say' => 'Prefer not to say',
    ];

    public const VALUE_CHAIN = [
        'production' => 'Production (growing, raising)',
        'input_supply' => 'Input supply',
        'processing' => 'Processing or packaging',
        'logistics' => 'Storage, transport or logistics',
        'trading' => 'Trading or marketing',
        'farm_services' => 'Farm services (machines, spraying, advice)',
    ];

    // ── Part 4: per course (stored on the enrollment) ──
    public const LEARNING_TYPE = [
        'course' => 'Course or training',
        'workshop' => 'Workshop or meeting',
        'mentoring' => 'Mentoring or coaching',
        'apprenticeship' => 'Apprenticeship or work placement (Dignity in Labour)',
    ];

    public const LEARNING_MODE = [
        'online' => 'Online (phone or computer)',
        'in_person' => 'In person at a hub or centre',
        'blended' => 'Both online and in person',
    ];

    public const HEARD_FROM = [
        'whatsapp' => 'WhatsApp',
        'social_media' => 'Facebook, TikTok, Instagram or X',
        'radio_tv' => 'Radio or TV',
        'friend_family' => 'Friend or family',
        'other' => 'Other',
    ];

    // ── Part 5: end-of-course check ──
    public const SURVEY_STAGES = ['completion' => 'At course completion', 'six_month' => 'Six months after completion'];

    public const WORK_CHANGE = [
        'same' => 'Same as before the course',
        'new_job' => 'Got a new paid job',
        'better_pay' => 'Better pay or more regular work in the same job',
        'started_enterprise' => 'Started my own agri-enterprise',
        'grew_business' => 'Grew my existing business',
        'further_study' => 'Went on to further study or training',
    ];

    public const RATING = ['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'];

    public const USED_LEARNING = ['yes' => 'Yes, already', 'planning' => 'Not yet, but I plan to', 'no' => 'No'];

    /** Laravel "in:" rule for an option list. */
    public static function in(array $options): string
    {
        return 'in:' . implode(',', array_is_list($options) ? $options : array_keys($options));
    }

    /** Validation for the profile fields (Parts 1–3), shared by register and profile update. */
    public static function profileRules(): array
    {
        return [
            'first_name' => 'nullable|string|max:100',
            'surname' => 'nullable|string|max:100',
            'nationality' => 'nullable|' . self::in(self::NATIONALITY),
            'has_disability' => 'nullable|' . self::in(self::YES_NO_PREFER),
            'disability_types' => 'nullable|array',
            'disability_types.*' => self::in(self::DISABILITY_TYPES),
            'displacement_status' => 'nullable|' . self::in(self::DISPLACEMENT),
            'women_category' => 'nullable|' . self::in(self::WOMEN_CATEGORY),
            'education_level' => 'nullable|' . self::in(self::EDUCATION),
            'locality_type' => 'nullable|' . self::in(self::LOCALITY),
            'learner_category' => 'nullable|' . self::in(self::LEARNER_CATEGORY),
            'main_work' => 'nullable|' . self::in(self::MAIN_WORK),
            'job_title' => 'nullable|string|max:255',
            'employment_type' => 'nullable|' . self::in(self::EMPLOYMENT_TYPE),
            'work_started_month' => 'nullable|integer|between:1,12',
            'work_started_year' => 'nullable|integer|between:1950,2100',
            'work_organisation' => 'nullable|string|max:255',
            'work_sector' => 'nullable|' . self::in(self::SECTOR),
            'main_commodity' => 'nullable|' . self::in(self::COMMODITY),
            'income_band' => 'nullable|' . self::in(self::INCOME),
            'value_chain' => 'nullable|' . self::in(self::VALUE_CHAIN),
        ];
    }

    public static function profileFields(): array
    {
        return array_values(array_filter(array_keys(self::profileRules()), fn ($k) => ! str_contains($k, '.')));
    }

    public static function enrollmentRules(): array
    {
        return [
            'learning_type' => 'nullable|' . self::in(self::LEARNING_TYPE),
            'learning_mode' => 'nullable|' . self::in(self::LEARNING_MODE),
            'heard_from' => 'nullable|' . self::in(self::HEARD_FROM),
            'partner_hub' => 'nullable|string|max:255',
        ];
    }
}
