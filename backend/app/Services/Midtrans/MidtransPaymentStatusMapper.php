<?php

namespace App\Services\Midtrans;

final class MidtransPaymentStatusMapper
{
    public static function toPaymentStatus(string $transactionStatus, ?string $fraudStatus): ?string
    {
        return match ($transactionStatus) {
            'settlement' => 'success',
            'capture' => self::mapCapture($fraudStatus),
            'pending' => 'pending',
            /** Kartu / alur Snap: kadang masih authorize sebelum capture — anggap menunggu finalisasi */
            'authorize' => 'pending',
            'deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund' => 'failed',
            default => null,
        };
    }

    private static function mapCapture(?string $fraudStatus): string
    {
        if ($fraudStatus === 'challenge') {
            return 'pending';
        }

        if ($fraudStatus === 'deny') {
            return 'failed';
        }

        return 'success';
    }
}
