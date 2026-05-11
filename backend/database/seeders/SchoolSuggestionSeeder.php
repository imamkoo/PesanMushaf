<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\SchoolSuggestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SchoolSuggestionSeeder extends Seeder
{
    private const JENJANG_API_SEGMENTS = [
        'SD' => 'sd',
        'SMP' => 'smp',
        'SMA' => 'sma',
    ];

    /**
     * Sinkronkan saran sekolah dari API direktori nasional (per provinsi),
     * lalu petakan ke kecamatan lewat normalisasi nama — selaras dengan
     * data kecamatan dari EMSIFA di DistrictSeeder (hanya nama, bukan kode BPS).
     */
    public function run(): void
    {
        $baseUrl = rtrim(config('services.school_directory.url'), '/');
        $provinsiKode = config('services.school_directory.provinsi_kode');
        $perPage = max(10, min(200, config('services.school_directory.per_page')));
        $pauseMs = max(0, config('services.school_directory.pause_ms'));

        $districts = District::query()->get(['id', 'name']);
        if ($districts->isEmpty()) {
            $this->command?->warn('SchoolSuggestionSeeder: tidak ada kecamatan di database. Jalankan DistrictSeeder terlebih dahulu.');

            return;
        }

        $districtByKec = [];
        foreach ($districts as $district) {
            $key = $this->normalizeKecamatanLabel($district->name);
            if ($key !== '') {
                $districtByKec[$key] = $district->id;
            }
        }

        SchoolSuggestion::query()->delete();

        $this->command?->info("SchoolSuggestionSeeder: mengambil data sekolah dari {$baseUrl} (provinsi {$provinsiKode})...");

        $created = 0;
        $skippedNoDistrict = 0;
        $skippedJenjang = 0;

        foreach (self::JENJANG_API_SEGMENTS as $educationLevel => $apiSegment) {
            $page = 1;
            $totalPages = 1;

            while (true) {
                $response = Http::withoutVerifying()
                    ->timeout(120)
                    ->retry(3, 1000)
                    ->get("{$baseUrl}/sekolah/{$apiSegment}", [
                        'provinsi' => $provinsiKode,
                        'page' => $page,
                        'perPage' => $perPage,
                    ]);

                if (! $response->successful()) {
                    $this->command?->error("SchoolSuggestionSeeder: HTTP {$response->status()} untuk {$apiSegment} halaman {$page}.");

                    break;
                }

                $payload = $response->json() ?? [];
                $rows = $payload['dataSekolah'] ?? [];

                if (! is_array($rows) || $rows === []) {
                    break;
                }

                $totalData = (int) ($payload['total_data'] ?? 0);
                $totalPages = max(1, (int) ceil($totalData / $perPage));

                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $bentuk = strtoupper(trim((string) ($row['bentuk'] ?? '')));
                    if ($bentuk !== $educationLevel) {
                        $skippedJenjang++;

                        continue;
                    }

                    $kecKey = $this->normalizeKecamatanLabel((string) ($row['kecamatan'] ?? ''));
                    $districtId = $districtByKec[$kecKey] ?? null;

                    if ($districtId === null) {
                        $skippedNoDistrict++;

                        continue;
                    }

                    $name = trim((string) ($row['sekolah'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $suggestion = SchoolSuggestion::firstOrCreate(
                        [
                            'district_id' => $districtId,
                            'education_level' => $educationLevel,
                            'name' => $name,
                        ],
                    );

                    if ($suggestion->wasRecentlyCreated) {
                        $created++;
                    }
                }

                $this->command?->info("SchoolSuggestionSeeder: {$apiSegment} halaman {$page}/{$totalPages} (total {$totalData})");

                if ($page >= $totalPages) {
                    break;
                }

                $page++;
                if ($pauseMs > 0) {
                    usleep($pauseMs * 1000);
                }
            }
        }

        $this->command?->info("SchoolSuggestionSeeder: selesai. Baris disimpan: {$created}, tanpa kecocokan kecamatan: {$skippedNoDistrict}, baris bentuk diabaikan: {$skippedJenjang}.");
    }

    /**
     * Samakan label "Kec. Pasar Minggu" dengan nama kecamatan di DB "Pasar Minggu".
     */
    private function normalizeKecamatanLabel(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^kec\\.\\s*/iu', '', $value) ?? $value;
        $value = trim($value);

        return Str::slug($value, '');
    }
}
