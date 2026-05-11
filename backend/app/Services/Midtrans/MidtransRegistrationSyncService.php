<?php

namespace App\Services\Midtrans;

use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class MidtransRegistrationSyncService
{
    /**
     * Tarik status transaksi dari Midtrans (GET Status API) dan samakan ke kolom payment_status.
     *
     * @return bool True jika status di database berubah.
     */
    public function syncPaymentStatus(Registration $registration): bool
    {
        MidtransConfigurator::apply();

        $orderId = $registration->registration_code;
        $baseUrl = \Midtrans\Config::getBaseUrl();
        $url = $baseUrl.'/v2/'.rawurlencode($orderId).'/status';
        $serverKey = (string) config('midtrans.server_key');

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->timeout(45)
                ->withOptions(['verify' => MidtransConfigurator::guzzleVerify()])
                ->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Tidak bisa menghubungi Midtrans Status API: '.$e->getMessage(),
                0,
                $e
            );
        }

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Midtrans Status HTTP '.$response->status().': '.$response->body()
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $transactionStatus = isset($body['transaction_status']) ? (string) $body['transaction_status'] : '';
        $fraudStatus = isset($body['fraud_status']) ? (string) $body['fraud_status'] : null;

        $newStatus = MidtransPaymentStatusMapper::toPaymentStatus($transactionStatus, $fraudStatus);

        if ($newStatus === null || $registration->payment_status === $newStatus) {
            return false;
        }

        $registration->update(['payment_status' => $newStatus]);

        return true;
    }
}
