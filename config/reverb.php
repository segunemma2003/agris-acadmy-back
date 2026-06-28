<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    | Start the server with:  php artisan reverb:start
    | Default port: 8080  (configurable via REVERB_SERVER_PORT)
    |
    | Production: Nginx proxies wss://academy-backends.agrisiti.com/ws/ → localhost:8080
    */

    'default' => 'reverb',

    'servers' => [
        'reverb' => [
            'host'     => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port'     => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST', '127.0.0.1'),
            'options'  => [
                'tls' => [],
            ],
            'max_request_size'     => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'ping_interval'        => env('REVERB_SERVER_PING_INTERVAL', 60),
            'max_payload_size'     => env('REVERB_MAX_PAYLOAD_SIZE', 10_000),
        ],
    ],

    'apps' => [
        [
            'key'    => env('REVERB_APP_KEY', 'agrisiti-chatbot-key'),
            'secret' => env('REVERB_APP_SECRET', 'agrisiti-chatbot-secret'),
            'app_id' => env('REVERB_APP_ID', 'agrisiti-chatbot'),
            'options' => [
                'host'   => env('REVERB_HOST', '127.0.0.1'),
                'port'   => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'allowed_origins'  => ['*'],
            'ping_interval'    => env('REVERB_APP_PING_INTERVAL', 60),
            'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
            'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10_000),
        ],
    ],

];
