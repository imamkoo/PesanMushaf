<?php

namespace App\Services\Midtrans;

use Midtrans\Config;

final class MidtransConfigurator
{
    public static function apply(): void
    {
        Config::$serverKey = (string) config('midtrans.server_key');
        Config::$clientKey = (string) config('midtrans.client_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
    }

    /**
     * Opsi verify untuk klien HTTP Guzzle (sertifikat / dev tanpa CA bundle).
     *
     * @return bool|string
     */
    public static function guzzleVerify(): bool|string
    {
        if (! (bool) config('midtrans.verify_ssl')) {
            return false;
        }

        $caInfo = config('midtrans.cainfo');
        if (is_string($caInfo) && $caInfo !== '' && is_readable($caInfo)) {
            return $caInfo;
        }

        return true;
    }
}
