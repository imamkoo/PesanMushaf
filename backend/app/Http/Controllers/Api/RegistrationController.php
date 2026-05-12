<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RegistrationResource;
use App\Http\Resources\Api\RegistrationStatusResource;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Support\IndonesianPhone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    /**
     * Menangani pendaftaran Mushaf dari API luar.
     * Dilindungi oleh Middleware 'check.api.key'.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        // Memastikan data yang dikirim sesuai dengan aturan bisnis Mushaf.
        // NIK & alamat hanya wajib bagi peserta jenjang UMUM (baik VIP maupun
        // Reguler) karena verifikasi tidak bisa lewat institusi sekolah formal.
        $requiresPersonalDocs = $request->input('education_level') === 'UMUM';

        $validator = Validator::make($request->all(), [
            'district_id' => 'required|exists:districts,id',
            'education_level' => 'required|in:SD,SMP,SMA,UMUM',
            'edition' => ['required', 'string', 'max:32', Rule::exists('price_categories', 'slug')->where('is_active', true)],
            'university_id' => ['nullable', 'integer', Rule::exists('universities', 'id')],
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'school_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'nik' => [$requiresPersonalDocs ? 'required' : 'nullable', 'string', 'digits:16'],
            'address' => [$requiresPersonalDocs ? 'required' : 'nullable', 'string', 'max:500'],
            'exclude_from_school_suggestions' => 'sometimes|boolean',
        ], [
            'nik.required' => 'NIK wajib diisi untuk peserta jenjang UMUM.',
            'nik.digits' => 'NIK harus 16 digit angka sesuai KTP.',
            'address.required' => 'Alamat wajib diisi untuk peserta jenjang UMUM.',
            'address.max' => 'Alamat maksimal 500 karakter.',
        ]);

        // Jika validasi gagal, kirim pesan error yang detail
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // 2. Jalankan Logika Pendaftaran di Service
            // Service ini yang menangani perhitungan harga, kode pendaftaran, dan nomor halaman
            $registration = RegistrationService::register($validator->validated());

            // 3. Eager Loading Relasi
            // Agar hasil JSON menyertakan nama kecamatan, bukan cuma ID-nya saja
            $registration->load(['district', 'batch']);

            // 4. Respon Sukses
            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil disimpan ke Mushaf!',
                'data' => RegistrationResource::make($registration),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            // Tangani jika terjadi error sistem (misal: database down atau logic error)
            $response = [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses pendaftaran.',
            ];

            if (config('app.debug')) {
                $response['error'] = $e->getMessage();
            }

            return response()->json($response, 500);
        }
    }

    /**
     * Cek status pendaftaran berdasarkan registration code.
     */
    public function statusLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lookup' => ['required', 'string', 'max:255'],
        ]);

        return $this->lookupRegistrationStatus($validated['lookup']);
    }

    /**
     * Cek status pendaftaran berdasarkan registration code.
     */
    public function status(string $registrationCode): JsonResponse
    {
        return $this->lookupRegistrationStatus($registrationCode);
    }

    private function lookupRegistrationStatus(string $lookupValue): JsonResponse
    {
        try {
            $lookup = mb_strtoupper((string) preg_replace('/\s+/', '', $lookupValue));
            $phoneNorm = IndonesianPhone::normalizeWhatsAppTarget($lookupValue);

            $registrations = Registration::query()
                ->with(['district', 'batch'])
                ->where(function ($query) use ($lookup, $phoneNorm) {
                    $query->where('registration_code', $lookup);
                    if ($phoneNorm !== '') {
                        $query->orWhere('phone_number', $phoneNorm);
                    }
                })
                ->orderByDesc('id')
                ->get();

            if ($registrations->isEmpty()) {
                throw new ModelNotFoundException;
            }

            $message = $registrations->count() > 1
                ? 'Beberapa pendaftaran ditemukan untuk data ini. Pilih detail di bawah.'
                : 'Status pendaftaran ditemukan.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $registrations
                    ->map(fn (Registration $registration) => RegistrationStatusResource::make($registration)->resolve())
                    ->values()
                    ->all(),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Kode pendaftaran atau nomor WhatsApp tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses status pendaftaran.',
            ];

            if (config('app.debug')) {
                $response['error'] = $e->getMessage();
            }

            return response()->json($response, 500);
        }
    }
}
