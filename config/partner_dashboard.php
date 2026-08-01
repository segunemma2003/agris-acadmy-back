<?php

// Single source of truth for which dashboard sections a Partner (funder) account
// can be granted. Used by Filament admin (UserResource) and PartnerDashboardController.
// Organisations (internship hosts) are a separate role and do not use this config.
return [
    'sections' => [
        'platform_overview' => [
            'label' => 'Platform Overview',
            'description' => 'Home view of students, courses (requirements & topics), enrollments, certificates and internships.',
            'icon' => 'globe',
        ],
        'courses' => [
            'label' => 'Courses',
            'description' => 'Full course breakdown — requirements, outcomes, modules, topics and videos.',
            'icon' => 'book',
        ],
        'course_performance' => [
            'label' => 'Course Performance',
            'description' => 'Average rating, total reviews, and the top courses by enrollment.',
            'icon' => 'star',
        ],
        'learners' => [
            'label' => 'Students',
            'description' => 'Student directory with gender and location filters.',
            'icon' => 'users',
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
        'engagement' => [
            'label' => 'Engagement',
            'description' => 'Quiz scores, pass rates, watch time and activity streaks.',
            'icon' => 'activity',
        ],
        'enrollments' => [
            'label' => 'Enrollments',
            'description' => 'Who enrolled, which course, and how much they paid.',
            'icon' => 'clipboard',
        ],
        'apprenticeships' => [
            'label' => 'Internships',
            'description' => 'Host companies, internship slots, placed/employed interns, attendance and performance.',
            'icon' => 'briefcase',
        ],
        'certificates' => [
            'label' => 'Certificates',
            'description' => 'Certificates issued with recipient and course details.',
            'icon' => 'certificate',
        ],
    ],
];
