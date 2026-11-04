<?php

use Illuminate\Support\Str;

return [
    // Si este archivo/proveedor no existe en otro checkout, ejecutar: php artisan horizon:install
    // APP_ENV observado en proyecto: local (.env.example), fallback production (config/app.php), staging añadido explícitamente.

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'defaults' => [
        'supervisor-push' => [
            'connection' => 'redis',
            'queue' => ['push-notifications'],
            'balance' => 'simple',
            'tries' => 3,
            'timeout' => 60,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-push' => [
                'maxProcesses' => 2,
            ],
        ],

        'staging' => [
            'supervisor-push' => [
                'maxProcesses' => 2,
            ],
        ],

        'local' => [
            'supervisor-push' => [
                'maxProcesses' => 1,
            ],
        ],
    ],

];
