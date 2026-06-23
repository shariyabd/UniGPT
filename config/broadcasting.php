<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Direct messaging broadcasts over Ably (ably/laravel-broadcaster +
    | @ably/laravel-echo). Ably is hosted, so nothing long-running is needed on
    | the (cPanel) host — broadcasting is one outbound HTTPS call per message.
    | Set BROADCAST_CONNECTION=null to disable realtime and fall back to polling.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
