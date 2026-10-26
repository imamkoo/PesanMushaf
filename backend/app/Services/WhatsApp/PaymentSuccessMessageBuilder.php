<?php

namespace App\Services\WhatsApp;

use App\Models\Registration;

final class PaymentSuccessMessageBuilder
{
    public function build(Registration $registration): string
    {
        return trim(implode("\n", [
            'Assalamu\'alaikum, pembayaran HUT500 berhasil.',
            '',
            'Kode pendaftaran: '.$registration->registration_code,
            'Nama: '.$registration->name,
            'Status: Lunas',
            'Total pembayaran: '.$this->formatRupiah((int) $registration->total_payment),
            '',
            'Simpan kode ini sebagai bukti pembayaran dan pengecekan status.',
        ]));
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
