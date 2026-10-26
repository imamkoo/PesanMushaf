<?php

namespace App\Services\WhatsApp;

use App\Models\Registration;

final class RegistrationSuccessMessageBuilder
{
    public function build(Registration $registration): string
    {
        $districtName = $registration->district?->name ?? '-';
        $batchName = $registration->batch?->name ?? '-';
        $editionLabel = $this->editionLabel((string) $registration->edition);

        return trim(implode("\n", [
            'Assalamu\'alaikum, pendaftaran HUT500 berhasil.',
            '',
            'Kode pendaftaran: '.$registration->registration_code,
            'Nama: '.$registration->name,
            'Jenjang: '.$registration->education_level,
            'Kategori: '.$editionLabel,
            'Kecamatan: '.$districtName,
            'Batch: '.$batchName,
            'Halaman: '.$registration->page_number,
            '',
            'Status pembayaran: Menunggu pembayaran',
            'Simpan kode ini untuk cek status dan pembayaran.',
        ]));
    }

    private function editionLabel(string $edition): string
    {
        return match (strtolower($edition)) {
            'vip' => 'VIP',
            'reguler' => 'Reguler',
            default => strtoupper($edition),
        };
    }
}
