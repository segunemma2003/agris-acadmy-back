<?php

// Single source of truth for which dashboard sections a Partner (funder) account
// can be granted. Used by Filament admin (UserResource) and PartnerDashboardController.
// Organisations (internship hosts) are a separate role and do not use this config.
return [
    'sections' => [
        'platform_overview' => [
            'label' => 'Platform Overview',
            'description' => 'Home view of students, courses, enrollments and enterprise hubs.',
            'icon' => 'globe',
        ],
        'courses' => [
            'label' => 'Courses',
            'description' => 'Browse the full course catalogue — requirements, outcomes, modules, topics and videos.',
            'icon' => 'book',
        ],
        'reports' => [
            'label' => 'Programme Reports',
            'description' => 'Weekly, monthly and custom impact reports sent by Agrisiti — with stats, participant lists, media and PDF download.',
            'icon' => 'document',
        ],
        'apprenticeships' => [
            'label' => 'Jobs Created / Enterprise Hub',
            'description' => 'Jobs enabled and enterprises created from the official TAGDEV workbooks.',
            'icon' => 'briefcase',
        ],
        'learners' => [
            'label' => 'Students',
            'description' => 'Student directory with gender and location filters.',
            'icon' => 'users',
        ],
        'enrollments' => [
            'label' => 'Enrollments',
            'description' => 'Who enrolled, which course, and how much they paid.',
            'icon' => 'clipboard',
        ],
        'engagement' => [
            'label' => 'Engagement',
            'description' => 'Quiz scores, pass rates, watch time and activity streaks.',
            'icon' => 'activity',
        ],
        'demographics' => [
            'label' => 'Demographics',
            'description' => 'Gender and age breakdowns with the matching student list.',
            'icon' => 'chart',
        ],
        'geography' => [
            'label' => 'Geography',
            'description' => 'Filter students by state or location and browse who lives where.',
            'icon' => 'map',
        ],
    ],

    // Removed from the partner dashboard; still stripped if present on older accounts.
    'retired_sections' => [
        'course_performance',
        'certificates',
    ],
];
