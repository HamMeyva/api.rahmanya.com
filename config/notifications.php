<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the default notification channel that is used
    | when no channel is specified in the notification class.
    |
    */

    'default' => env('NOTIFICATION_CHANNEL', 'mongodb'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the notification channels that are supported
    | by your application. The 'mongodb' channel is used to store notifications
    | in the MongoDB database.
    |
    */
    'channels' => [
        'mongodb' => [
            'driver' => 'mongodb',
            'collection' => 'notifications',
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'notifications',
        ],
        'mail' => [
            'driver' => 'mail',
            'queue' => true,
        ],
        'broadcast' => [
            'driver' => 'broadcast',
            'queue' => true,
        ],
    ],
];
