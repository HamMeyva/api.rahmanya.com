<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NetGSM Credentials
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for the NetGSM API
    |
    */

    'credentials' => [
        'usercode' => env('NETGSM_USERCODE', ''),
        'password' => env('NETGSM_PASSWORD', ''),
        'header' => env('NETGSM_HEADER', ''), // SMS Başlığı (Gönderici adı)
    ],

    /*
    |--------------------------------------------------------------------------
    | NetGSM SMS Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS services
    |
    */
    'sms' => [
        'use_otp' => env('NETGSM_USE_OTP', false),
        'otp_template' => env('NETGSM_OTP_TEMPLATE', 'Doğrulama kodunuz: {code}'),
        'otp_length' => env('NETGSM_OTP_LENGTH', 6),
        'otp_expiry' => env('NETGSM_OTP_EXPIRY', 5), // minutes
        'default_language' => env('NETGSM_DEFAULT_LANGUAGE', 'tr'),
    ],
];
