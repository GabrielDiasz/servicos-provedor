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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', true),
        'url' => env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000'),
        'token' => env('WHATSAPP_TOKEN'),
        'timeout' => env('WHATSAPP_TIMEOUT', 10),
        'connect_timeout' => env('WHATSAPP_CONNECT_TIMEOUT', 3),
    ],

    'sgp' => [
        'enabled' => env('SGP_ENABLED', true),
        'url' => env('SGP_BASE_URL', 'https://seu-sgp.exemplo'),
        'app' => env('SGP_APP'),
        'token' => env('SGP_TOKEN'),
        'web_username' => env('SGP_WEB_USERNAME'),
        'web_password' => env('SGP_WEB_PASSWORD'),
        'default_responsavel' => env('SGP_DEFAULT_RESPONSAVEL'),
        'tecnico_responsavel_map' => [
            env('SGP_TECH_MATCHER_A') => env('SGP_RESPONSAVEL_A'),
            env('SGP_TECH_MATCHER_B') => env('SGP_RESPONSAVEL_B'),
            env('SGP_TECH_MATCHER_C') => env('SGP_RESPONSAVEL_C'),
        ],
        'timeout' => env('SGP_TIMEOUT', 15),
        'connect_timeout' => env('SGP_CONNECT_TIMEOUT', 5),
    ],

];
