<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\District;
use App\Models\PriceCategory;
use App\Models\Registration;
use App\Services\WhatsApp\RegistrationSuccessWhatsAppNotifier;
use App\Support\IndonesianPhone;
use App\Support\SchoolNameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RegistrationService
{
    public const BATCH_CAPACITY = 603;

    public const KAMPUNG_EDITION = 'kampung';

    /**
     * Penanda institution_type khusus batch Mushaf Kampung. Membuat batch Kampung
     * tidak pernah cocok dengan filter batch Reguler (yang mencocokkan
     * institution_type pendaftar) maupun hitungan nomor batch numerik Reguler.
     */
    public const KAMPUNG_BATCH_TYPE = 'KAMPUNG';

    public static function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $edition = $data['edition'] ?? 'reguler';
            $level = $data['education_level'];
            // Klien lama belum mengirim institution_type. Turunkan dari jenjang agar
            // peserta UMUM tidak masuk batch sekolah dan tetap bergabung dengan batch
            // UMUM yang sudah di-backfill migrasi.
            $institutionType = $data['institution_type'] ?? ($level === 'UMUM' ? 'UMUM' : 'SEKOLAH');
            $schoolName = trim((string) ($data['school_name'] ?? ''));

            $registrantDistrict = District::findOrFail($data['district_id']);

            // Peserta Mushaf Kampung umumnya individu tanpa institusi. Kolom school_name
            // tetap diisi supaya segmen Smart Code dan laporan admin tidak kosong.
            if ($edition === self::KAMPUNG_EDITION && $schoolName === '') {
                $schoolName = "Mushaf Kampung {$registrantDistrict->name}";
            }

            $schoolNameNormalized = SchoolNameNormalizer::normalize($schoolName);

            // 1. Tentukan / buat batch sesuai edisi
            $batch = match ($edition) {
                'vip' => self::resolveVipGlobalBatch(),
                self::KAMPUNG_EDITION => self::resolveKampungBatch($registrantDistrict),
                default => self::resolveRegulerBatch($level, $institutionType, $schoolNameNormalized, $registrantDistrict),
            };

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
                'institution_type' => $institutionType,
                'university_id' => $data['university_id'] ?? null,
                'mosque_id' => $data['mosque_id'] ?? null,
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

            // 7. Sinkronkan flag penuh dengan okupansi aktif batch.
            $batch->syncFullness($pageNumber);

            DB::afterCommit(function () use ($registration): void {
                self::sendRegistrationSuccessWhatsApp($registration);
            });

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
            ->whereFullByOccupancy(false)
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
     * Cari atau buat batch Mushaf Kampung per kecamatan (mencampur semua jenjang
     * dan jenis institusi). Format cetak A3 berwarna berbeda dari Reguler A4,
     * sehingga batch-nya tidak boleh tercampur dengan batch Reguler/VIP.
     */
    private static function resolveKampungBatch(District $registrantDistrict): Batch
    {
        // Kunci baris kecamatan agar dua pendaftar Kampung pertama yang datang
        // bersamaan tidak sama-sama membuat batch baru (bentrok slug unik).
        District::query()->whereKey($registrantDistrict->id)->lockForUpdate()->first();

        $batch = Batch::query()
            ->whereFullByOccupancy(false)
            ->where('institution_type', self::KAMPUNG_BATCH_TYPE)
            ->where('district_id', $registrantDistrict->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            return $batch;
        }

        // withTrashed: slug batch unik termasuk baris soft-deleted, jadi nomor
        // berikutnya harus melewati batch yang pernah dihapus.
        $nextNumber = Batch::withTrashed()
            ->where('institution_type', self::KAMPUNG_BATCH_TYPE)
            ->where('district_id', $registrantDistrict->id)
            ->count() + 1;

        return Batch::create([
            'district_id' => $registrantDistrict->id,
            'education_level' => 'UMUM',
            'institution_type' => self::KAMPUNG_BATCH_TYPE,
            'batch_number' => 'K'.$nextNumber,
            'name' => "Mushaf Kampung {$registrantDistrict->name} {$nextNumber}",
            'max_capacity' => self::BATCH_CAPACITY,
            'is_full' => false,
        ]);
    }

    /**
     * Cari atau buat batch Reguler dengan urutan prioritas:
     *   1) Batch yang sudah berisi institusi persis sama (cocok via school_name_normalized
     *      supaya variasi penulisan tidak memecah klaster)
     *   2) Batch yang sudah berisi pendaftar dari kecamatan yang sama
     *   3) Batch yang sudah berisi pendaftar satu kota (4 digit pertama kode kecamatan)
     *   4) Batch kosong yang dibuat admin untuk kecamatan tersebut
     *   5) Batch baru, anchor pada kecamatan pendaftar
     *
     * Batch sekolah dipisah per jenjang (SD/SMP/SMA). Batch institusi non-sekolah
     * (MASJID/KOMUNITAS/UMUM) dipisah per jenis institusi, bukan per jenjang.
     * Batch lama tanpa institution_type tetap dianggap batch sekolah.
     */
    private static function resolveRegulerBatch(
        string $level,
        string $institutionType,
        ?string $schoolNameNormalized,
        District $registrantDistrict,
    ): Batch {
        $kotaCode = substr((string) $registrantDistrict->code, 0, 4);
        $isSchool = $institutionType === 'SEKOLAH';

        $filterInstitutionType = function ($query) use ($institutionType, $isSchool): void {
            $query->where('institution_type', $institutionType);

            if ($isSchool) {
                $query->orWhereNull('institution_type');
            }
        };

        $baseQuery = fn () => Batch::query()
            ->whereFullByOccupancy(false)
            ->where($filterInstitutionType)
            ->when($isSchool, fn ($query) => $query->where('education_level', $level))
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

        // Priority 4: batch yang baru dibuat admin dan belum punya pendaftar.
        // Tanpa langkah ini, batch kosong tidak pernah dipakai dan sistem membuat
        // batch duplikat pada pendaftaran pertama.
        if (! $batch) {
            $batch = (clone $baseQuery())
                ->where('district_id', $registrantDistrict->id)
                ->whereDoesntHave('registrations')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if ($batch) {
            return $batch;
        }

        // Priority 5: new batch anchored to registrant district
        // Cast eksplisit ke integer agar Postgres tidak melakukan lex-sort
        // (mis. "9" > "10") setelah 10+ batch sejenis tercipta.
        $lastNumberQuery = Batch::query()
            ->where($filterInstitutionType)
            ->where('name', 'not like', '%(GOR)%');

        if ($isSchool) {
            $lastNumberQuery->where('education_level', $level);
        }

        $lastNumber = (int) $lastNumberQuery->max(DB::raw('CAST(batch_number AS INTEGER)'));

        $nextNumber = $lastNumber + 1;
        $label = $isSchool ? $level : $institutionType;

        return Batch::create([
            'district_id' => $registrantDistrict->id,
            'education_level' => $level,
            'institution_type' => $institutionType,
            'batch_number' => (string) $nextNumber,
            'name' => "Mushaf Reguler {$label} {$registrantDistrict->name} {$nextNumber}",
            'max_capacity' => self::BATCH_CAPACITY,
            'is_full' => false,
        ]);
    }

    private static function sendRegistrationSuccessWhatsApp(Registration $registration): void
    {
        try {
            app(RegistrationSuccessWhatsAppNotifier::class)->send($registration);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
