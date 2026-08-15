<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    // Amazon Bedrock — VR Studio scene generation (and future AWS AI)
    'bedrock' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        // Enable this model under Bedrock → Model access. Inference profiles use a "us." prefix.
        'model_id' => env('AWS_BEDROCK_MODEL_ID', 'anthropic.claude-3-5-haiku-20241022-v1:0'),
        'max_tokens' => (int) env('AWS_BEDROCK_MAX_TOKENS', 4096),
    ],

    'africastalking' => [
        'username' => env('AT_USERNAME', 'sandbox'),
        'key'      => env('AT_API_KEY'),
        'hash_key' => env('AT_HASH_KEY'),
    ],

    // Academy SPA base URL (Path B links in certificate emails, etc.)
    'frontend' => [
        'url' => env('FRONTEND_URL', 'https://academy.agrisiti.com'),
    ],

    // Agrisiti Finance (Lend) — graduate funding bridge CTAs + service auth
    'finance' => [
        'url' => env('FINANCE_URL', 'https://lend.agrisiti.com'),
        'api_url' => env('FINANCE_API_URL', 'https://lend-api.agrisiti.com'),
        'service_key' => env('ACADEMY_INTERNAL_API_KEY', env('FINANCE_SERVICE_KEY')),
        // Master switch + fallback amount if Finance public settings are unreachable
        'graduate_funding_enabled' => filter_var(env('FINANCE_GRADUATE_FUNDING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'graduate_eligible_loan_amount' => (float) env('FINANCE_GRADUATE_ELIGIBLE_LOAN_AMOUNT', 7500000),
    ],

    // VR Studio sibling workspace (Option B — WebXR authoring + player)
    'vr_studio' => [
        'url' => env('VR_STUDIO_URL', 'https://agrisiti-vr-studio.netlify.app'),
    ],

    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
            'port' => env('PUSHER_PORT', 443),
            'scheme' => env('PUSHER_SCHEME', 'https'),
            'encrypted' => true,
            'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
        ],
    ],

];
