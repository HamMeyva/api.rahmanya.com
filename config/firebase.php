<?php

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */
    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */
    'projects' => [
        'app' => [
            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can create one
             * in the Firebase console:
             * https://console.firebase.google.com/project/_/settings/serviceaccounts/adminsdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * with the configuration below.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * autodiscover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a service.
             *
             */
            'credentials' => [
                'file' => env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
                'auto_discovery' => true,
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Database
             * ------------------------------------------------------------------------
             */
            'database' => [
                'url' => env('FIREBASE_DATABASE_URL'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Cloud Messaging
             * ------------------------------------------------------------------------
             *
             * Your Firebase Cloud Messaging Server API key
             * Can be found at https://console.firebase.google.com/project/_/settings/cloudmessaging
             */
            'cloud_messaging' => [
                'key' => env('FIREBASE_FCM_KEY'),
                'sender_id' => env('FIREBASE_FCM_SENDER_ID'),
                'token_server_key' => env('FIREBASE_FCM_TOKEN_SERVER_KEY'),
            ],
        ],
    ],
];
