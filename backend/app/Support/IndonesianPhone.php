<?php

namespace App\Support;

final class IndonesianPhone
{
    /**
     * Ubah input nomor Indonesia menjadi digit internasional 62… untuk WhatsApp.
     */
    public static function normalizeWhatsAppTarget(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8') && strlen($digits) >= 9) {
            return '62'.$digits;
        }

        return $digits;
    }
}
