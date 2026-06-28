<?php

/**
 * Scoring weights for course recommendations based on intake quiz answers.
 * Keys are quiz answer values; nested keys are course category slugs.
 * Higher weight = stronger match.
 */

return [
    'occupation' => [
        'Farmer'               => ['crop-farming' => 3, 'livestock' => 2, 'hydroponics' => 1, 'agri-tech' => 1],
        'Student'              => ['agri-tech' => 3, 'hydroponics' => 2, 'crop-farming' => 1, 'livestock' => 1],
        'Agri-business owner'  => ['agri-tech' => 3, 'crop-farming' => 2, 'hydroponics' => 2, 'livestock' => 1],
        'Researcher'           => ['agri-tech' => 3, 'hydroponics' => 3, 'crop-farming' => 1, 'livestock' => 1],
        'Other'                => ['crop-farming' => 1, 'hydroponics' => 1, 'agri-tech' => 1, 'livestock' => 1],
    ],

    'goal' => [
        'Start a farm'         => ['crop-farming' => 3, 'livestock' => 2, 'hydroponics' => 2, 'agri-tech' => 1],
        'Improve my farm'      => ['agri-tech' => 3, 'crop-farming' => 2, 'hydroponics' => 2, 'livestock' => 2],
        'Learn agri-tech'      => ['agri-tech' => 3, 'hydroponics' => 2, 'crop-farming' => 1, 'livestock' => 1],
        'Get certified'        => ['agri-tech' => 2, 'crop-farming' => 2, 'hydroponics' => 2, 'livestock' => 2],
        'Other'                => ['crop-farming' => 1, 'hydroponics' => 1, 'agri-tech' => 1, 'livestock' => 1],
    ],

    'experience' => [
        'None'         => ['beginner' => 3, 'intermediate' => 0, 'advanced' => 0],
        'Beginner'     => ['beginner' => 3, 'intermediate' => 1, 'advanced' => 0],
        'Intermediate' => ['beginner' => 1, 'intermediate' => 3, 'advanced' => 1],
        'Expert'       => ['beginner' => 0, 'intermediate' => 1, 'advanced' => 3],
    ],

    'language' => [
        'en' => ['en' => 2, 'ha' => 0],
        'ha' => ['ha' => 2, 'en' => 1],
    ],
];
