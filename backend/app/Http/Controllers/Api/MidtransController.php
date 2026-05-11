<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CreatesSnapPaymentToken;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\Midtrans\MidtransRegistrationSyncService;
use App\Services\Midtrans\MidtransPaymentStatusMapper;
use App\Services\Midtrans\NotificationSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MidtransController extends Controller
{
    public function createSnapToken(Request $request, CreatesSnapPaymentToken $snapTokenService): JsonResponse
    {
        $validated = $request->validate([
            'registration_code' => ['required', 'string', 'max:255'],
        ]);

        $code = mb_strtoupper((string) preg_replace('/\s+/', '', $validated['registration_code']));

        $registration = Registration::query()
            ->where('registration_code', $code)
            ->first();

        if ($registration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Kode pendaftaran tidak ditemukan.',
            ], 404);
        }

        if ($registration->payment_status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran untuk pendaftaran ini sudah berhasil.',
            ], 422);
        }

        try {
            $token = $snapTokenService->createTokenForRegistration($registration);
        } catch (Throwable $e) {
            report($e);

            $body = [
                'success' => false,
                'message' => 'Gagal menghubungi penyedia pembayaran. Silakan coba lagi.',
            ];

            if (config('app.debug')) {
                $body['error'] = $e->getMessage();
            }

            return response()->json($body, 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token Snap siap digunakan.',
            'data' => [
                'snap_token' => $token,
                'client_key' => config('midtrans.client_key'),
                'order_id' => $registration->registration_code,
                'is_production' => (bool) config('midtrans.is_production'),
            ],
        ]);
    }

    /**
     * Sinkronkan payment_status dari Midtrans Status API (setelah Snap sukses / bila webhook tertunda).
     */
    public function syncStatus(Request $request, MidtransRegistrationSyncService $syncService): JsonResponse
    {
        $validated = $request->validate([
            'registration_code' => ['required', 'string', 'max:255'],
        ]);

        $code = mb_strtoupper((string) preg_replace('/\s+/', '', $validated['registration_code']));

        $registration = Registration::query()
            ->where('registration_code', $code)
            ->first();

        if ($registration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Kode pendaftaran tidak ditemukan.',
            ], 404);
        }

        $previousStatus = $registration->payment_status;

        try {
            $syncService->syncPaymentStatus($registration);
        } catch (Throwable $e) {
            report($e);

            $body = [
                'success' => false,
                'message' => 'Gagal menyinkronkan status pembayaran.',
            ];

            if (config('app.debug')) {
                $body['error'] = $e->getMessage();
            }

            return response()->json($body, 502);
        }

        $registration->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran diperbarui.',
            'data' => [
                'payment_status' => $registration->payment_status,
            ],
        ]);
    }

    public function notification(Request $request, NotificationSignatureVerifier $signatureVerifier): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        if (! $signatureVerifier->isAuthentic($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak valid.',
            ], 400);
        }

        $orderId = isset($payload['order_id']) ? (string) $payload['order_id'] : '';
        $orderIdNormalized = mb_strtoupper((string) preg_replace('/\s+/', '', $orderId));

        $registration = Registration::query()
            ->where('registration_code', $orderIdNormalized)
            ->first();

        if ($registration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $grossAmount = $payload['gross_amount'] ?? null;
        if ($grossAmount !== null && (int) round((float) $grossAmount) !== (int) $registration->total_payment) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran tidak cocok.',
            ], 400);
        }

        $transactionStatus = isset($payload['transaction_status']) ? (string) $payload['transaction_status'] : '';
        $fraudStatus = isset($payload['fraud_status']) ? (string) $payload['fraud_status'] : null;

        $newStatus = MidtransPaymentStatusMapper::toPaymentStatus($transactionStatus, $fraudStatus);

        $previousStatus = $registration->payment_status;

        if ($newStatus !== null && $previousStatus !== $newStatus) {
            $registration->update(['payment_status' => $newStatus]);
        }

        return response()->json(['success' => true, 'message' => 'OK']);
    }
}
