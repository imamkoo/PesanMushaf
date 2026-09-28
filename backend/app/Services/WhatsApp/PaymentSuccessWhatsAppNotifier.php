<?php

namespace App\Services\WhatsApp;

use App\Models\Registration;

final class PaymentSuccessWhatsAppNotifier
{
    use ResolvesWhatsAppRecipient;

    public function __construct(
        private readonly GreenApiClient $greenApiClient,
        private readonly PaymentSuccessMessageBuilder $messageBuilder,
    ) {
    }

    public function send(
        Registration $registration,
        ?string $forceRecipient = null,
        bool $ignoreAlreadyNotified = false,
    ): ?string {
        if (! $this->shouldSend() || ! $this->shouldSendForRegistration($registration, $ignoreAlreadyNotified)) {
            return null;
        }

        $recipient = $this->resolveRecipient($registration, $forceRecipient);
        if ($recipient === '') {
            return null;
        }

        $registration->loadMissing(['district', 'batch']);

        $this->greenApiClient->sendText(
            $recipient,
            $this->messageBuilder->build($registration),
        );

        $registration->forceFill([
            'whatsapp_payment_notified_at' => now(),
        ])->saveQuietly();

        return $recipient;
    }

    public function shouldSend(): bool
    {
        return (bool) config('whatsapp.enabled')
            && (bool) config('whatsapp.payment_success.enabled')
            && $this->greenApiClient->isConfigured();
    }

    private function shouldSendForRegistration(Registration $registration, bool $ignoreAlreadyNotified): bool
    {
        if ($registration->payment_status !== 'success') {
            return false;
        }

        return $ignoreAlreadyNotified || $registration->whatsapp_payment_notified_at === null;
    }
}
