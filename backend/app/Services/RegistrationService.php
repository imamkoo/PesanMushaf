<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\District;
use App\Models\PriceCategory;
use App\Models\Registration;
use App\Support\IndonesianPhone;
use App\Support\SchoolNameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public const BATCH_CAPACITY = 603;

    public static function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $edition = $data['edition'] ?? 'reguler';
            $level = $data['education_level'];
            $schoolName = $data['school_name'];
            $schoolNameNormalized = SchoolNameNormalizer::normalize($schoolName);

            $registrantDistrict = District::findOrFail($data['district_id']);

            // 1. Tentukan / buat batch sesuai edisi
            $batch = $edition === 'vip'
                ? self::resolveVipGlobalBatch()
                : self::resolveRegulerBatch($level, $schoolNameNormalized, $registrantDistrict);

            // 2. Kalkulasi halaman dalam batch tsb. Tidak butuh lockForUpdate() karena
            //    baris batches sudah dikunci di resolve*Batch() dan Postgres melarang
            //    FOR UPDATE pada query agregat (COUNT).
            $pageNumber = Registration::where('batch_id', $batch->id)->count() + 1;

            // 3. Validasi harga
            $priceCategory = PriceCategory::query()
                ->where('slug', $edition)
                ->active()
                ->first();

            if ($priceCategory === null) {
                throw new \InvalidArgumentException("Kategori harga \"{$edition}\" tidak aktif atau tidak ditemukan.");
            }

            // 4. Normalisasi WhatsApp
            $phone = IndonesianPhone::normalizeWhatsAppTarget((string) $data['phone_number']);
            if ($phone === '' || strlen($phone) < 11) {
                throw new \InvalidArgumentException('Nomor WhatsApp tidak valid. Gunakan 628… atau 08….');
            }

            $basePrice = $priceCategory->amount;

            // 5. Smart Code: pakai kecamatan & jenjang ASLI pendaftar
            $schoolClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $schoolName));
            $schoolId = substr($schoolClean, 0, 6);
            $randomId = strtoupper(Str::random(4));
            $editionCode = strtoupper($edition);

            $smartCode = sprintf(
                '%s-%s-%s-MUSHAF%s-%03d-%s-%s',
                $registrantDistrict->code,
                $editionCode,
                $level,
                $batch->batch_number,
                $pageNumber,
                $schoolId,
                $randomId
            );

            // 6. Simpan registrasi
            $registration = Registration::create([
                'batch_id' => $batch->id,
                'district_id' => $registrantDistrict->id,
                'education_level' => $level,
                'university_id' => $data['university_id'] ?? null,
                'name' => $data['name'],
                'phone_number' => $phone,
                'email' => $data['email'] ?? null,
                'nik' => $data['nik'] ?? null,
                'address' => $data['address'] ?? null,
                'school_name' => $schoolName,
                'exclude_from_school_suggestions' => (bool) ($data['exclude_from_school_suggestions'] ?? false),
                'registration_code' => $smartCode,
                'page_number' => $pageNumber,
                'edition' => $edition,
                'base_price' => $basePrice,
                'total_payment' => $basePrice,
                'payment_status' => 'pending',
            ]);

            // 7. Tutup batch jika kapasitas tercapai
            if ($pageNumber >= self::BATCH_CAPACITY) {
                $batch->update(['is_full' => true]);
            }

            return $registration;
        });
    }

    /**
     * Cari atau buat batch VIP global (1 batch untuk seluruh DKI Jakarta,
     * mencampur semua jenjang).
     */
    private static function resolveVipGlobalBatch(): Batch
    {
        $batch = Batch::query()
            ->where('is_full', false)
            ->where('name', 'like', 'Mushaf VIP Jakarta%')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            return $batch;
        }

        $existingVipGlobalCount = Batch::query()
            ->where('name', 'like', 'Mushaf VIP Jakarta%')
            ->count();

        $nextNumber = $existingVipGlobalCount + 1;

        return Batch::create([
            'district_id' => null,
            'education_level' => null,
            'batch_number' => 'V'.$nextNumber,
            'name' => "Mushaf VIP Jakarta {$nextNumber} (GOR)",
            'max_capacity' => self::BATCH_CAPACITY,
            'is_full' => false,
        ]);
    }

    /**
     * Cari atau buat batch Reguler untuk jenjang tertentu dengan urutan prioritas:
     *   1) Batch yang sudah berisi sekolah persis sama (cocok via school_name_normalized
     *      supaya variasi penulisan tidak memecah klaster)
     *   2) Batch yang sudah berisi pendaftar dari kecamatan yang sama
     *   3) Batch yang sudah berisi pendaftar satu kota (4 digit pertama kode kecamatan)
     *   4) Batch baru, anchor pada kecamatan pendaftar
     */
    private static function resolveRegulerBatch(string $level, ?string $schoolNameNormalized, District $registrantDistrict): Batch
    {
        $kotaCode = substr((string) $registrantDistrict->code, 0, 4);

        $baseQuery = fn () => Batch::query()
            ->where('is_full', false)
            ->where('education_level', $level)
            ->where('name', 'not like', '%(GOR)%');

        // Priority 1: same school (normalized)
        $batch = $schoolNameNormalized !== null
            ? (clone $baseQuery())
                ->whereHas('registrations', fn ($q) => $q->where('school_name_normalized', $schoolNameNormalized))
                ->orderBy('id')
                ->lockForUpdate()
                ->first()
            : null;

        // Priority 2: same kecamatan (district_id)
        if (! $batch) {
            $batch = (clone $baseQuery())
                ->whereHas('registrations', fn ($q) => $q->where('district_id', $registrantDistrict->id))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        // Priority 3: same kota (district.code prefix)
        if (! $batch && $kotaCode !== '') {
            $batch = (clone $baseQuery())
                ->whereHas(
                    'registrations.district',
                    fn ($q) => $q->where('code', 'like', $kotaCode.'%')
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if ($batch) {
            return $batch;
        }

        // Priority 4: new batch anchored to registrant district
        // Cast eksplisit ke integer agar Postgres tidak melakukan lex-sort
        // (mis. "9" > "10") setelah 10+ batch ber-jenjang sama tercipta.
        $lastNumber = (int) Batch::query()
            ->where('education_level', $level)
            ->where('name', 'not like', '%(GOR)%')
            ->max(DB::raw('CAST(batch_number AS INTEGER)'));

        $nextNumber = $lastNumber + 1;

        return Batch::create([
            'district_id' => $registrantDistrict->id,
            'education_level' => $level,
            'batch_number' => (string) $nextNumber,
            'name' => "Mushaf Reguler {$level} {$registrantDistrict->name} {$nextNumber}",
            'max_capacity' => self::BATCH_CAPACITY,
            'is_full' => false,
        ]);
    }
}
