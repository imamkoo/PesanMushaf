<?php

return [
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    /**
     * Set true di production. Di Mac/Homebrew kadang file CA bundle hilang sehingga cURL error
     * "error adding trust anchors from file" — sementara bisa false hanya di lokal.
     */
    'verify_ssl' => filter_var(env('MIDTRANS_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
    /** Path opsional ke cacert.pem (mis. unduh dari https://curl.se/ca/cacert.pem). */
    'cainfo' => env('MIDTRANS_CAINFO'),
];