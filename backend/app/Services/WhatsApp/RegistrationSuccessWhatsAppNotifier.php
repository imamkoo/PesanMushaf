<?php

namespace App\Services\WhatsApp;

use App\Models\Registration;

final class RegistrationSuccessWhatsAppNotifier
{
    use ResolvesWhatsAppRecipient;

    public function __construct(
        private readonly GreenApiClient $greenApiClient,
        private readonly RegistrationSuccessMessageBuilder $messageBuilder,
    ) {
    }

    public function send(Registration $registration, ?string $forceRecipient = null): ?string
    {
        if (! $this->shouldSend()) {
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

        return $recipient;
    }

    public function shouldSend(): bool
    {
        return (bool) config('whatsapp.enabled')
            && (bool) config('whatsapp.registration_success.enabled')
            && $this->greenApiClient->isConfigured();
    }
}
