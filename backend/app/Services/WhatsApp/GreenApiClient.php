<?php

namespace App\Services\WhatsApp;

use App\Support\IndonesianPhone;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GreenApiClient
{
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== ''
            && $this->idInstance() !== ''
            && $this->apiToken() !== '';
    }

    public function sendText(string $phoneNumber, string $message): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Green API belum dikonfigurasi lengkap di .env.');
        }

        $normalizedPhone = IndonesianPhone::normalizeWhatsAppTarget($phoneNumber);
        if ($normalizedPhone === '') {
            throw new RuntimeException('Nomor tujuan WhatsApp tidak valid.');
        }

        $response = Http::acceptJson()
            ->timeout($this->timeoutSeconds())
            ->withOptions(['verify' => $this->guzzleVerifyOption()])
            ->post($this->sendMessageUrl(), [
                'chatId' => $normalizedPhone.'@c.us',
                'message' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Green API HTTP '.$response->status().': '.$response->body()
            );
        }
    }

    private function sendMessageUrl(): string
    {
        return $this->baseUrl()
            .'/waInstance'.$this->idInstance()
            .'/sendMessage/'
            .$this->apiToken();
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('whatsapp.green_api.url'), '/');
    }

    private function idInstance(): string
    {
        return trim((string) config('whatsapp.green_api.id_instance'));
    }

    private function apiToken(): string
    {
        return trim((string) config('whatsapp.green_api.api_token'));
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('whatsapp.green_api.timeout', 20));
    }

    /**
     * @return bool|string
     */
    private function guzzleVerifyOption(): bool|string
    {
        if (! (bool) config('whatsapp.green_api.verify_ssl', true)) {
            return false;
        }

        $caInfo = config('whatsapp.green_api.cainfo');

        return is_string($caInfo) && trim($caInfo) !== ''
            ? trim($caInfo)
            : true;
    }
}
