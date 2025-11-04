<?php

$customPusherHost = env('PUSHER_HOST');
$hasCustomPusher = !empty($customPusherHost);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                // Para Pusher Cloud, NO definas host/port/scheme en .env.
                // Solo si defines PUSHER_HOST, se aplicarán host/port/scheme.
                'host' => $hasCustomPusher ? $customPusherHost : null,
                'port' => $hasCustomPusher ? env('PUSHER_PORT', 6001) : null,
                'scheme' => $hasCustomPusher ? env('PUSHER_SCHEME', 'http') : null,
                // TLS por defecto (Pusher Cloud requiere TLS). Puedes sobreescribir con PUSHER_ENCRYPTED/PUSHER_USE_TLS si necesitas.
                'encrypted' => env('PUSHER_ENCRYPTED', true),
                'useTLS' => env('PUSHER_USE_TLS', true),
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
