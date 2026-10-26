<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications
    |--------------------------------------------------------------------------
    |
    | Untuk mode demo / sandbox Green API, Anda bisa mengaktifkan notifikasi
    | lalu mengarahkan semua pesan ke satu nomor uji lewat WHATSAPP_TEST_TO.
    | Ini membantu menghemat jatah chat unik bulanan.
    |
    */
    'enabled' => filter_var(env('WHATSAPP_NOTIFICATIONS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'test_to' => env('WHATSAPP_TEST_TO'),

    'registration_success' => [
        'enabled' => filter_var(env('WHATSAPP_REGISTRATION_SUCCESS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'payment_success' => [
        'enabled' => filter_var(env('WHATSAPP_PAYMENT_SUCCESS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'green_api' => [
        'url' => env('GREEN_API_URL'),
        'id_instance' => env('GREEN_API_ID_INSTANCE'),
        'api_token' => env('GREEN_API_TOKEN_INSTANCE'),
        'timeout' => (int) env('GREEN_API_TIMEOUT', 20),
        'verify_ssl' => filter_var(env('GREEN_API_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
        'cainfo' => env('GREEN_API_CAINFO'),
    ],
];
