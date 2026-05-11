<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\District;
use App\Models\University;
use App\Services\RegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MasterStressTest extends Command
{
    protected $signature = 'app:master-stress-test {count=2000}';
    protected $description = 'Master scenario stress test for registration and logistics flow';

    /**
     * @var array<string, int>
     */
    private array $scenarioWeights = [
        'vip_global_mixed_levels' => 25,
        'reguler_school_cluster' => 25,
        'reguler_kota_spread' => 15,
        'reguler_umum_with_docs' => 10,
        'invalid_district' => 5,
        'invalid_umum_without_university' => 5,
        'edge_case_identity' => 15,
    ];

    private const REGULER_SCHOOL_POOL = [
        'SD Negeri Cluster 01',
        'SMP Negeri Cluster 02',
        'SMA Negeri Cluster 03',
    ];

    public function handle(): int
    {
        $total = (int) $this->argument('count');
        if ($total <= 0) {
            $this->error('Count harus lebih dari 0.');

            return self::FAILURE;
        }

        $districts = District::all();
        $universities = University::all();

        if ($districts->isEmpty() || $universities->isEmpty()) {
            $this->error('Gagal: data kecamatan atau universitas masih kosong.');

            return self::FAILURE;
        }

        $targetDistrict = $districts->first();
        $sameKotaDistricts = $this->districtsSharingKota($districts, $targetDistrict);
        $minimumTwoFullBatchCapacity = RegistrationService::BATCH_CAPACITY * 2;
        $forcedTargetQuota = min($total, $minimumTwoFullBatchCapacity);

        $this->info("Memulai master stress test dengan {$total} skenario.");
        $this->warn("Target district surge: {$targetDistrict->name} (kode {$targetDistrict->code})");
        $this->line('Forced VIP global surge quota: ' . $forcedTargetQuota);
        $this->line('Sama-kota dengan target: ' . $sameKotaDistricts->pluck('name')->join(', '));

        if ($total < $minimumTwoFullBatchCapacity) {
            $this->warn('Count di bawah ' . $minimumTwoFullBatchCapacity . ', jadi belum bisa menjamin 2 batch penuh dalam satu run.');
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = [
            'attempted' => 0,
            'success' => 0,
            'failed' => 0,
            'expected_failures' => 0,
            'unexpected_failures' => 0,
            'unexpected_successes' => 0,
            'scenario' => [],
            'logistics' => [
                'district' => [],
                'education' => [],
                'edition' => [],
            ],
        ];

        $invalidDistrictId = ((int) $districts->max('id')) + 100000;

        for ($i = 1; $i <= $total; $i++) {
            $scenario = $i <= $forcedTargetQuota
                ? 'vip_global_mixed_levels'
                : $this->pickScenario();

            $payload = $this->buildPayload(
                scenario: $scenario,
                index: $i,
                targetDistrict: $targetDistrict,
                districts: $districts,
                sameKotaDistricts: $sameKotaDistricts,
                universities: $universities,
                invalidDistrictId: $invalidDistrictId,
            );

            // Stress test selalu tandai eksklusi agar nama dummy tidak masuk
            // autocomplete sekolah meskipun di masa depan kita memutuskan
            // kembali memanggil sumber registrations.
            $payload['exclude_from_school_suggestions'] = true;

            $expectedToFail = in_array(
                $scenario,
                ['invalid_umum_without_university', 'invalid_district'],
                true
            );

            try {
                RegistrationService::register($payload);
                $stats['success']++;

                if ($expectedToFail) {
                    $stats['unexpected_successes']++;
                }

                $this->incrementScenarioStats($stats, $scenario, 'success');
                $this->incrementLogisticsStats($stats, $payload);
            } catch (\Throwable) {
                $stats['failed']++;
                $this->incrementScenarioStats($stats, $scenario, 'failed');

                if ($expectedToFail) {
                    $stats['expected_failures']++;
                } else {
                    $stats['unexpected_failures']++;
                }
            }

            $stats['attempted']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->renderStats($stats, $targetDistrict);

        return self::SUCCESS;
    }

    private function pickScenario(): string
    {
        $roll = random_int(1, array_sum($this->scenarioWeights));
        $cursor = 0;

        foreach ($this->scenarioWeights as $scenario => $weight) {
            $cursor += $weight;
            if ($roll <= $cursor) {
                return $scenario;
            }
        }

        return 'edge_case_identity';
    }

    /**
     * @param Collection<int, District> $districts
     * @param Collection<int, District> $sameKotaDistricts
     * @param Collection<int, University> $universities
     * @return array<string, int|string|null>
     */
    private function buildPayload(
        string $scenario,
        int $index,
        District $targetDistrict,
        Collection $districts,
        Collection $sameKotaDistricts,
        Collection $universities,
        int $invalidDistrictId,
    ): array {
        $fallbackName = "Test User {$index}";
        $fallbackPhone = '0812' . str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT);

        return match ($scenario) {
            'vip_global_mixed_levels' => [
                'district_id' => $districts->random()->id,
                'education_level' => ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)],
                'edition' => 'vip',
                'name' => "{$fallbackName} VIP",
                'phone_number' => $fallbackPhone,
                'school_name' => 'Sekolah/Univ VIP ' . random_int(1, 25),
                'nik' => self::generateNik(),
                'address' => 'Jl. VIP Tester No. ' . random_int(1, 999),
            ],
            'reguler_school_cluster' => [
                'district_id' => $targetDistrict->id,
                'education_level' => $level = ['SD', 'SMP', 'SMA'][random_int(0, 2)],
                'edition' => 'reguler',
                'name' => "{$fallbackName} REG-CLUSTER",
                'phone_number' => $fallbackPhone,
                'school_name' => self::REGULER_SCHOOL_POOL[array_search(
                    $level,
                    ['SD', 'SMP', 'SMA'],
                    true,
                ) ?: 0],
            ],
            'reguler_kota_spread' => (function () use ($targetDistrict, $sameKotaDistricts, $fallbackName, $fallbackPhone, $index) {
                $district = $sameKotaDistricts->isNotEmpty()
                    ? $sameKotaDistricts->random()
                    : $targetDistrict;
                $level = ['SD', 'SMP', 'SMA'][random_int(0, 2)];

                return [
                    'district_id' => $district->id,
                    'education_level' => $level,
                    'edition' => 'reguler',
                    'name' => "{$fallbackName} REG-KOTA",
                    'phone_number' => $fallbackPhone,
                    'school_name' => "Sekolah Spread {$level}#" . (($index % 7) + 1),
                ];
            })(),
            'reguler_umum_with_docs' => [
                'district_id' => $districts->random()->id,
                'education_level' => 'UMUM',
                'edition' => 'reguler',
                'name' => "{$fallbackName} REG-UMUM",
                'phone_number' => $fallbackPhone,
                'school_name' => 'Perguruan Tinggi Umum ' . random_int(1, 50),
                'nik' => self::generateNik(),
                'address' => 'Jl. Umum Tester No. ' . random_int(1, 999),
            ],
            'invalid_umum_without_university' => [
                'district_id' => $districts->random()->id,
                'education_level' => 'UMUM',
                'edition' => random_int(0, 1) === 1 ? 'vip' : 'reguler',
                'name' => "{$fallbackName} INVALID-UNI",
                'phone_number' => $fallbackPhone,
                'school_name' => 'Perguruan Tinggi Tanpa Universitas',
                'nik' => self::generateNik(),
                'address' => 'Jl. Invalid Uni',
            ],
            'invalid_district' => [
                'district_id' => $invalidDistrictId,
                'education_level' => 'SMA',
                'edition' => 'reguler',
                'name' => "{$fallbackName} INVALID-DIST",
                'phone_number' => $fallbackPhone,
                'school_name' => 'Sekolah Invalid District',
            ],
            'edge_case_identity' => [
                'district_id' => $districts->random()->id,
                'education_level' => $edgeLevel = ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)],
                'edition' => random_int(0, 1) === 1 ? 'vip' : 'reguler',
                'name' => "QA {$index} " . fake()->name(),
                'phone_number' => '0812' . str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
                'school_name' => $edgeLevel === 'UMUM'
                    ? 'Perguruan Tinggi Edge#' . random_int(100, 999)
                    : 'SMK-Al_Hikmah#' . random_int(100, 999),
                'nik' => $edgeLevel === 'UMUM' ? self::generateNik() : null,
                'address' => $edgeLevel === 'UMUM'
                    ? 'Jl. Edge#' . random_int(1, 999)
                    : null,
            ],
            default => [
                'district_id' => $districts->random()->id,
                'education_level' => $cityLevel = ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)],
                'edition' => random_int(1, 100) <= 35 ? 'vip' : 'reguler',
                'name' => "{$fallbackName} CITY",
                'phone_number' => $fallbackPhone,
                'school_name' => $cityLevel === 'UMUM'
                    ? 'Perguruan Tinggi Kota'
                    : 'Sekolah Kota ' . random_int(1, 200),
            ],
        };
    }

    /**
     * @param Collection<int, District> $districts
     * @return Collection<int, District>
     */
    private function districtsSharingKota(Collection $districts, District $target): Collection
    {
        $kota = substr((string) $target->code, 0, 4);

        if ($kota === '') {
            return collect();
        }

        return $districts->filter(
            fn (District $d) => substr((string) $d->code, 0, 4) === $kota && $d->id !== $target->id
        )->values();
    }

    private static function generateNik(): string
    {
        $nik = '';
        for ($i = 0; $i < 16; $i++) {
            $nik .= (string) random_int(0, 9);
        }

        return $nik;
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function incrementScenarioStats(array &$stats, string $scenario, string $key): void
    {
        if (! isset($stats['scenario'][$scenario])) {
            $stats['scenario'][$scenario] = ['attempted' => 0, 'success' => 0, 'failed' => 0];
        }

        $stats['scenario'][$scenario]['attempted']++;
        $stats['scenario'][$scenario][$key]++;
    }

    /**
     * @param array<string, mixed> $stats
     * @param array<string, int|string|null> $payload
     */
    private function incrementLogisticsStats(array &$stats, array $payload): void
    {
        $districtName = District::find($payload['district_id'])?->name ?? 'INVALID_DISTRICT';
        $educationLevel = (string) $payload['education_level'];
        $edition = (string) $payload['edition'];

        $stats['logistics']['district'][$districtName] = ($stats['logistics']['district'][$districtName] ?? 0) + 1;
        $stats['logistics']['education'][$educationLevel] = ($stats['logistics']['education'][$educationLevel] ?? 0) + 1;
        $stats['logistics']['edition'][$edition] = ($stats['logistics']['edition'][$edition] ?? 0) + 1;
    }

    /**
     * @param array<string, int> $distribution
     */
    private function printTopDistribution(string $label, array $distribution): void
    {
        arsort($distribution);
        $this->line("- {$label}:");

        foreach (array_slice($distribution, 0, 5, true) as $name => $count) {
            $this->line("  • {$name}: {$count}");
        }
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function renderStats(array $stats, District $targetDistrict): void
    {
        $this->info('Testing selesai.');
        $this->line("Total Attempt          : <fg=cyan>{$stats['attempted']}</>");
        $this->line("Success                : <fg=green>{$stats['success']}</>");
        $this->line("Failed                 : <fg=red>{$stats['failed']}</>");
        $this->line("Expected Failures      : <fg=yellow>{$stats['expected_failures']}</>");
        $this->line("Unexpected Failures    : <fg=red>{$stats['unexpected_failures']}</>");
        $this->line("Unexpected Successes   : <fg=red>{$stats['unexpected_successes']}</>");
        $this->newLine();

        $this->info('Breakdown per skenario:');
        foreach ($this->scenarioWeights as $scenario => $_) {
            $scenarioData = $stats['scenario'][$scenario] ?? ['attempted' => 0, 'success' => 0, 'failed' => 0];
            $this->line(sprintf(
                '- %s -> attempt: %d | success: %d | failed: %d',
                $scenario,
                $scenarioData['attempted'],
                $scenarioData['success'],
                $scenarioData['failed']
            ));
        }

        $this->newLine();
        $this->info('Ringkasan distribusi sukses untuk tim logistik:');
        $this->printTopDistribution('District', $stats['logistics']['district']);
        $this->printTopDistribution('Education', $stats['logistics']['education']);
        $this->printTopDistribution('Edition', $stats['logistics']['edition']);

        $this->newLine();
        $this->info('Audit batch VIP global (Mushaf VIP Jakarta%):');
        $vipBatches = Batch::query()
            ->where('name', 'like', 'Mushaf VIP Jakarta%')
            ->orderBy('id')
            ->get();

        if ($vipBatches->isEmpty()) {
            $this->error('Tidak ada batch VIP global yang terbentuk.');
        } else {
            foreach ($vipBatches as $batch) {
                $regCount = $batch->registrations()->count();
                $statusColor = $batch->is_full ? 'green' : 'yellow';
                $statusText = $batch->is_full ? '[PENUH]' : "[TERISI {$regCount}/" . RegistrationService::BATCH_CAPACITY . ']';
                $districtCol = $batch->district_id === null ? 'NULL (global)' : (string) $batch->district_id;
                $levelCol = $batch->education_level ?? 'NULL (mixed)';
                $this->line(
                    "- Jilid {$batch->batch_number} (district={$districtCol}, level={$levelCol}): "
                    . "<fg={$statusColor}>{$statusText}</> -> {$batch->name}"
                );
            }
        }

        $this->newLine();
        $this->info("Audit batch Reguler di kecamatan target {$targetDistrict->name}:");
        $regBatches = Batch::query()
            ->where('name', 'not like', '%(GOR)%')
            ->where(function ($q) use ($targetDistrict) {
                $q->where('district_id', $targetDistrict->id)
                    ->orWhereHas('registrations', fn ($r) => $r->where('district_id', $targetDistrict->id));
            })
            ->orderBy('id')
            ->get();

        if ($regBatches->isEmpty()) {
            $this->warn('Tidak ada batch Reguler yang menyentuh kecamatan target.');
        } else {
            foreach ($regBatches as $batch) {
                $regCount = $batch->registrations()->count();
                $distinctSchool = $batch->registrations()->distinct('school_name')->count('school_name');
                $distinctDistrict = $batch->registrations()->distinct('district_id')->count('district_id');
                $statusColor = $batch->is_full ? 'green' : 'yellow';
                $statusText = $batch->is_full ? '[PENUH]' : "[TERISI {$regCount}/" . RegistrationService::BATCH_CAPACITY . ']';
                $this->line(
                    "- Jilid {$batch->batch_number} ({$batch->education_level}): "
                    . "<fg={$statusColor}>{$statusText}</> | sekolah berbeda: {$distinctSchool} | "
                    . "kecamatan berbeda: {$distinctDistrict} -> {$batch->name}"
                );
            }
        }

        $this->newLine();
        $this->info('Cek dashboard Filament untuk validasi visual akhir.');
    }
}
