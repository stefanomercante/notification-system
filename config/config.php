<?php

return [
    'app' => [
        'name' => 'Notification System',
        'env' => 'development',
        'debug' => true,
        'timezone' => 'Europe/Rome',
    ],

    'database' => [
        'host' => 'mysql',
        'port' => 3306,
        'database' => 'notifications',
        'username' => 'notifyuser',
        'password' => 'notifypass',
        'charset' => 'utf8mb4',
    ],

    'redis' => [
        'host' => 'redis',
        'port' => 6379,
        'database' => 0,
        'prefix' => 'notify:',
    ],

    'queue' => [
        'default' => 'redis',
        'connections' => [
            'redis' => [
                'driver' => 'redis',
                'queue' => 'default',
                'retry_after' => 90,
            ],
        ],
    ],

    'channels' => [
        'email' => [
            'driver' => 'smtp',
            'host' => 'mailhog',
            'port' => 1025,
            'username' => null,
            'password' => null,
            'encryption' => null,
            'from' => [
                'address' => 'noreply@notification-system.local',
                'name' => 'Notification System',
            ],
        ],

        'sms' => [
            'driver' => 'twilio',
            'account_sid' => getenv('TWILIO_ACCOUNT_SID'),
            'auth_token' => getenv('TWILIO_AUTH_TOKEN'),
            'from' => getenv('TWILIO_FROM_NUMBER'),
        ],

        'webhook' => [
            'driver' => 'http',
            'timeout' => 10,
            'verify_ssl' => true,
        ],
    ],

    'retry' => [
        'max_attempts' => 3,
        'delay' => 5, // seconds
        'backoff' => 'exponential', // linear, exponential
    ],

    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'file' => __DIR__ . '/../logs/app.log',
    ],
];
