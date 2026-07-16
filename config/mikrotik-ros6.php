<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mikrotik Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the mikrotik connections below you wish
    | to use as your default connection for all router work. Of course
    | you may use many connections at once using the Manager class.
    |
    */

    'default' => env('MIKROTIK_ROS6_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Mikrotik Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the mikrotik connections setup for your application.
    | This supports a hybrid approach: you can define static connections
    | here (e.g. for testing) or pass dynamic arrays from your database
    | (like the `routers` table) directly to the Facade at runtime.
    |
    */

    'connections' => [

        'default' => [
            'host' => env('MIKROTIK_ROS6_HOST', '192.168.1.1'),
            'username' => env('MIKROTIK_ROS6_USERNAME', 'admin'),
            'password' => env('MIKROTIK_ROS6_PASSWORD', ''),
            'port' => env('MIKROTIK_ROS6_PORT', 8728),
            'ssl' => env('MIKROTIK_ROS6_SSL', false),
            'timeout' => env('MIKROTIK_ROS6_TIMEOUT', 3),
            'attempts' => env('MIKROTIK_ROS6_ATTEMPTS', 3),
            'delay' => env('MIKROTIK_ROS6_DELAY', 1),
            'debug' => env('MIKROTIK_ROS6_DEBUG', false),
        ],

    ],
];
