<?php

use App\Support\SchoolNameNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Snapshot data DMI DKI (9.344 masjid) diimpor sekali saat deploy produksi.
     * Data tidak dimasukkan pada test/local agar suite tetap cepat dan bersih.
     * Setelah migrasi ini, sinkronisasi berikutnya memakai `php artisan mosque:import`.
     */
    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $path = database_path('data/dmi-mosques-dki.csv.gz');

        if (! is_file($path)) {
            throw new RuntimeException("Snapshot DMI tidak ditemukan: {$path}");
        }

        $handle = @fopen('compress.zlib://'.$path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Snapshot DMI tidak dapat dibuka: {$path}");
        }

        $header = fgetcsv($handle, null, ',', '"', '');

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('Snapshot DMI tidak memiliki header CSV.');
        }

        $now = now();
        $buffer = [];

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            if (! is_array($data) || empty($data['dmi_code']) || empty($data['name'])) {
                continue;
            }

            $buffer[] = [
                'dmi_code' => $data['dmi_code'],
                'external_ids' => null,
                'name' => $data['name'],
                'name_normalized' => SchoolNameNormalizer::normalize($data['name']),
                'address' => $data['address'] !== '' ? $data['address'] : null,
                'village' => $data['village'] !== '' ? $data['village'] : null,
                'district_name' => $data['district_name'] !== '' ? $data['district_name'] : null,
                'city' => $data['city'] !== '' ? $data['city'] : null,
                'province' => $data['province'] !== '' ? $data['province'] : null,
                'latitude' => $data['latitude'] !== '' ? $data['latitude'] : null,
                'longitude' => $data['longitude'] !== '' ? $data['longitude'] : null,
                'phone' => null,
                'source' => 'dmi',
                'source_url' => 'https://dmi.or.id/data-masjid/',
                'is_verified' => false,
                'is_active' => true,
                'last_synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($buffer) === 500) {
                $this->upsert($buffer);
                $buffer = [];
            }
        }

        fclose($handle);

        if ($buffer !== []) {
            $this->upsert($buffer);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function upsert(array $rows): void
    {
        DB::table('mosques')->upsert(
            $rows,
            ['dmi_code'],
            [
                'name',
                'name_normalized',
                'address',
                'village',
                'district_name',
                'city',
                'province',
                'latitude',
                'longitude',
                'source',
                'source_url',
                'last_synced_at',
                'updated_at',
            ]
        );
    }

    public function down(): void
    {
        // Sengaja no-op: rollback tidak boleh menghapus katalog masjid yang
        // mungkin sudah diverifikasi atau dipakai pendaftaran.
    }
};
