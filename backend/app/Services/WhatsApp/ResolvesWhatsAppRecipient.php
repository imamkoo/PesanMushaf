<?php

namespace App\Services\WhatsApp;

use App\Models\Registration;

trait ResolvesWhatsAppRecipient
{
    private function resolveRecipient(Registration $registration, ?string $forceRecipient): string
    {
        if ($forceRecipient !== null && trim($forceRecipient) !== '') {
            return trim($forceRecipient);
        }

        $testRecipient = trim((string) config('whatsapp.test_to'));
        if ($testRecipient !== '') {
            return $testRecipient;
        }

        return trim((string) $registration->phone_number);
    }
}
