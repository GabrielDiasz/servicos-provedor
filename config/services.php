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
        'image_timeout' => env('WHATSAPP_IMAGE_TIMEOUT', 120),
        'connect_timeout' => env('WHATSAPP_CONNECT_TIMEOUT', 3),
        'chrome_path' => env('CHROME_PATH'),
    ],

    'sgp' => [
        'enabled' => env('SGP_ENABLED', true),
        'url' => env('SGP_BASE_URL', 'https://seu-sgp.exemplo'),
        'app' => env('SGP_APP'),
        'token' => env('SGP_TOKEN'),
        'web_username' => env('SGP_WEB_USERNAME'),
        'web_password' => env('SGP_WEB_PASSWORD'),
        'default_responsavel' => env('SGP_DEFAULT_RESPONSAVEL'),
        'responsavel_usuario_map' => (static function (): array {
            $pares = [
                [
                    'matchers' => [env('ATTENDANT_PABLO_EMAIL'), env('ATTENDANT_PABLO_NAME')],
                    'responsaveis' => [
                        env('ATTENDANT_PABLO_SGP_RESPONSAVEL_NOME'),
                        env('ATTENDANT_PABLO_SGP_RESPONSAVEL_LOGIN'),
                    ],
                    'portal_username' => env('ATTENDANT_PABLO_SGP_RESPONSAVEL_LOGIN'),
                    'portal_password' => env('ATTENDANT_PABLO_SGP_RESPONSAVEL_PASSWORD'),
                ],
                [
                    'matchers' => [env('ATTENDANT_PAULO_EMAIL'), env('ATTENDANT_PAULO_NAME')],
                    'responsaveis' => [
                        env('ATTENDANT_PAULO_SGP_RESPONSAVEL_NOME'),
                        env('ATTENDANT_PAULO_SGP_RESPONSAVEL_LOGIN'),
                    ],
                    'portal_username' => env('ATTENDANT_PAULO_SGP_RESPONSAVEL_LOGIN'),
                    'portal_password' => env('ATTENDANT_PAULO_SGP_RESPONSAVEL_PASSWORD'),
                ],
            ];

            $mapa = [];

            foreach ($pares as $par) {
                $matchers = array_values(array_filter(array_map(
                    static fn ($valor) => trim((string) $valor),
                    $par['matchers'] ?? []
                )));
                $responsaveis = array_values(array_filter(array_map(
                    static fn ($valor) => trim((string) $valor),
                    $par['responsaveis'] ?? []
                )));

                if ($matchers !== [] && $responsaveis !== []) {
                    $mapa[] = [
                        'matchers' => $matchers,
                        'responsaveis' => $responsaveis,
                        'portal_username' => trim((string) ($par['portal_username'] ?? '')),
                        'portal_password' => trim((string) ($par['portal_password'] ?? '')),
                    ];
                }
            }

            return $mapa;
        })(),
        'tecnico_responsavel_map' => (static function (): array {
            $pares = [
                ['matcher' => env('SGP_TECH_MATCHER_JHON'), 'responsavel' => env('SGP_RESPONSAVEL_JHON')],
                ['matcher' => env('SGP_TECH_MATCHER_VANDERLEY'), 'responsavel' => env('SGP_RESPONSAVEL_VANDERLEY')],
                ['matcher' => env('SGP_TECH_MATCHER_TESTE'), 'responsavel' => env('SGP_RESPONSAVEL_TESTE')],
                ['matcher' => env('SGP_TECH_MATCHER_A'), 'responsavel' => env('SGP_RESPONSAVEL_A')],
                ['matcher' => env('SGP_TECH_MATCHER_B'), 'responsavel' => env('SGP_RESPONSAVEL_B')],
                ['matcher' => env('SGP_TECH_MATCHER_C'), 'responsavel' => env('SGP_RESPONSAVEL_C')],
            ];

            $mapa = [];

            foreach ($pares as $par) {
                $matcher = trim((string) ($par['matcher'] ?? ''));
                $responsavel = trim((string) ($par['responsavel'] ?? ''));

                if ($matcher !== '' && $responsavel !== '') {
                    $mapa[$matcher] = $responsavel;
                }
            }

            return $mapa;
        })(),
        'timeout' => env('SGP_TIMEOUT', 15),
        'connect_timeout' => env('SGP_CONNECT_TIMEOUT', 5),
    ],

];
