<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\WhatsApp\RegistrationSuccessWhatsAppNotifier;
use Illuminate\Console\Command;
use Throwable;

class SendRegistrationWhatsApp extends Command
{
    protected $signature = 'app:send-registration-whatsapp {registration_code : Kode pendaftaran yang mau dikirim ulang} {--to= : Override nomor tujuan (628...)}';

    protected $description = 'Kirim ulang notifikasi WhatsApp registrasi berhasil via Green API';

    public function handle(RegistrationSuccessWhatsAppNotifier $notifier): int
    {
        $code = mb_strtoupper((string) preg_replace('/\s+/', '', (string) $this->argument('registration_code')));

        $registration = Registration::query()
            ->with(['district', 'batch'])
            ->where('registration_code', $code)
            ->first();

        if ($registration === null) {
            $this->error('Kode pendaftaran tidak ditemukan.');

            return self::FAILURE;
        }

        try {
            $recipient = $notifier->send(
                $registration,
                $this->option('to') ?: null,
            );
        } catch (Throwable $e) {
            report($e);
            $this->error('Gagal mengirim WhatsApp: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($recipient === null) {
            $this->warn('Notifikasi dilewati. Cek config WhatsApp di .env: aktifkan flag dan isi GREEN_API_*.');

            return self::FAILURE;
        }

        $this->info("Notifikasi WhatsApp berhasil dikirim ke {$recipient}.");

        return self::SUCCESS;
    }
}
