<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\WhatsApp\PaymentSuccessWhatsAppNotifier;
use Illuminate\Console\Command;
use Throwable;

class SendPaymentSuccessWhatsApp extends Command
{
    protected $signature = 'app:send-payment-success-whatsapp {registration_code : Kode pendaftaran yang mau dikirim ulang} {--to= : Override nomor tujuan (628...)}';

    protected $description = 'Kirim ulang notifikasi WhatsApp pembayaran berhasil via Green API';

    public function handle(PaymentSuccessWhatsAppNotifier $notifier): int
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

        if ($registration->payment_status !== 'success') {
            $this->error('Pendaftaran ini belum berstatus pembayaran berhasil.');

            return self::FAILURE;
        }

        try {
            $recipient = $notifier->send(
                $registration,
                $this->option('to') ?: null,
                true,
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

        $this->info("Notifikasi WhatsApp pembayaran berhasil dikirim ke {$recipient}.");

        return self::SUCCESS;
    }
}
