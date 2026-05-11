<?php

namespace App\Services\Midtrans;

use App\Contracts\CreatesSnapPaymentToken;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Midtrans\Config;
use Midtrans\Sanitizer;
use RuntimeException;
use Throwable;

final class SnapTokenService implements CreatesSnapPaymentToken
{
    public function createTokenForRegistration(Registration $registration): string
    {
        MidtransConfigurator::apply();

        $payload = $this->buildSnapPayload($registration);
        $url = Config::getSnapBaseUrl().'/transactions';
        $serverKey = (string) config('midtrans.server_key');

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(45)
                ->withOptions(['verify' => MidtransConfigurator::guzzleVerify()])
                ->post($url, $payload);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Tidak bisa menghubungi Midtrans Snap: '.$e->getMessage(),
                0,
                $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Midtrans Snap HTTP '.$response->status().': '.$response->body()
            );
        }

        $token = $response->json('token');
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Midtrans Snap tidak mengembalikan token.');
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapPayload(Registration $registration): array
    {
        $grossAmount = (int) $registration->total_payment;

        $params = [
            'transaction_details' => [
                'order_id' => $registration->registration_code,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $this->firstName($registration->name),
                'last_name' => $this->lastName($registration->name),
                'email' => $registration->email ?: 'guest-'.$registration->id.'@pending.hut500.invalid',
                'phone' => $registration->phone_number,
            ],
            'item_details' => [
                [
                    'id' => 'registration-'.$registration->id,
                    'price' => $grossAmount,
                    'quantity' => 1,
                    'name' => 'Pendaftaran Mushaf HUT500',
                ],
            ],
        ];

        $creditCardDefaults = [
            'credit_card' => [
                'secure' => Config::$is3ds,
            ],
        ];

        if (isset($params['item_details'])) {
            $sum = 0;
            foreach ($params['item_details'] as $item) {
                $sum += $item['quantity'] * $item['price'];
            }
            $params['transaction_details']['gross_amount'] = $sum;
        }

        if (Config::$isSanitized) {
            Sanitizer::jsonRequest($params);
        }

        /** @var array<string, mixed> */
        return array_replace_recursive($creditCardDefaults, $params);
    }

    private function firstName(string $fullName): string
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return 'Pendaftar';
        }

        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);

        return $parts[0] ?? 'Pendaftar';
    }

    private function lastName(string $fullName): string
    {
        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || count($parts) < 2) {
            return $parts[0] ?? 'HUT500';
        }

        return implode(' ', array_slice($parts, 1));
    }
}
