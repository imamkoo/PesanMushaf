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

    /*
    |--------------------------------------------------------------------------
    | Direktori sekolah (referensi luar)
    |--------------------------------------------------------------------------
    |
    | Data diisi lewat SchoolSuggestionSeeder — sama seperti wilayah (EMSIFA)
    | dan universitas (Hipolabs). Kode provinsi memakai format referensi
    | Kemendikbud pada API tersebut (DKI Jakarta = 010000).
    |
    */
    'school_directory' => [
        'url' => env('SCHOOL_DIRECTORY_API_URL', 'https://api-sekolah-indonesia.vercel.app'),
        'provinsi_kode' => env('SCHOOL_DIRECTORY_PROVINSI_KODE', '010000'),
        'per_page' => (int) env('SCHOOL_DIRECTORY_PER_PAGE', 100),
        'pause_ms' => (int) env('SCHOOL_DIRECTORY_PAUSE_MS', 50),
    ],

];
