<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\District;
use App\Models\SchoolSuggestion;
use App\Models\University;
use App\Services\RegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MasterStressTest extends Command
{
    protected $signature = 'app:master-stress-test {count=2000} {--allow-live-data : Izinkan run di environment non-local/testing}';

    protected $description = 'Master scenario stress test for registration and logistics flow';

    /**
     * @var array<string, int>
     */
    private array $scenarioWeights = [
        'vip_global_mixed_levels' => 25,
        'reguler_target_surge' => 0,
        'reguler_school_cluster' => 25,
        'reguler_kota_spread' => 15,
        'reguler_umum_with_docs' => 10,
        'invalid_district' => 5,
        'invalid_umum_without_docs' => 5,
        'edge_case_identity' => 15,
    ];

    private const REGULER_SCHOOL_POOL = [
        'SD Negeri Cluster 01',
        'SMP Negeri Cluster 02',
        'SMA Negeri Cluster 03',
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) && ! $this->option('allow-live-data')) {
            $this->error('Mode aman aktif: jalankan stress test hanya di local/testing, atau gunakan --allow-live-data bila Anda benar-benar memahami risikonya.');

            return self::FAILURE;
        }

        $total = (int) $this->argument('count');
        if ($total <= 0) {
            $this->error('Count harus lebih dari 0.');

            return self::FAILURE;
        }

        $districts = District::all();
        $universities = University::all();
        $schoolCatalog = $this->buildSchoolCatalog();

        if ($districts->isEmpty() || $universities->isEmpty() || $schoolCatalog->isEmpty()) {
            $this->error('Gagal: data kecamatan, universitas, atau katalog sekolah masih kosong.');

            return self::FAILURE;
        }

        $targetDistrict = $districts->first();
        $sameKotaDistricts = $this->districtsSharingKota($districts, $targetDistrict);
        $minimumTwoFullBatchCapacity = RegistrationService::BATCH_CAPACITY * 2;
        $forcedVipQuota = min($total, RegistrationService::BATCH_CAPACITY);
        $forcedRegulerQuota = min(
            max($total - $forcedVipQuota, 0),
            RegistrationService::BATCH_CAPACITY,
        );

        $this->info("Memulai master stress test dengan {$total} skenario.");
        $this->warn("Target district surge: {$targetDistrict->name} (kode {$targetDistrict->code})");
        $this->line('Forced VIP global surge quota: '.$forcedVipQuota);
        $this->line('Forced Reguler target surge quota: '.$forcedRegulerQuota);
        $this->line('Sama-kota dengan target: '.$sameKotaDistricts->pluck('name')->join(', '));

        if ($total < $minimumTwoFullBatchCapacity) {
            $this->warn('Count di bawah '.$minimumTwoFullBatchCapacity.', jadi belum bisa menjamin 2 batch penuh dalam satu run.');
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
            $scenario = match (true) {
                $i <= $forcedVipQuota => 'vip_global_mixed_levels',
                $i <= ($forcedVipQuota + $forcedRegulerQuota) => 'reguler_target_surge',
                default => $this->pickScenario(),
            };

            $payload = $this->buildPayload(
                scenario: $scenario,
                index: $i,
                targetDistrict: $targetDistrict,
                districts: $districts,
                sameKotaDistricts: $sameKotaDistricts,
                universities: $universities,
                schoolCatalog: $schoolCatalog,
                invalidDistrictId: $invalidDistrictId,
            );

            // Stress test selalu tandai eksklusi agar nama dummy tidak masuk
            // autocomplete sekolah meskipun di masa depan kita memutuskan
            // kembali memanggil sumber registrations.
            $payload['exclude_from_school_suggestions'] = true;

            $expectedToFail = in_array(
                $scenario,
                ['invalid_umum_without_docs', 'invalid_district'],
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
     * @param  Collection<int, District>  $districts
     * @param  Collection<int, District>  $sameKotaDistricts
     * @param  Collection<int, University>  $universities
     * @param  Collection<string, Collection<int, string>>  $schoolCatalog
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $scenario,
        int $index,
        District $targetDistrict,
        Collection $districts,
        Collection $sameKotaDistricts,
        Collection $universities,
        Collection $schoolCatalog,
        int $invalidDistrictId,
    ): array {
        $fallbackName = "Test User {$index}";
        $fallbackPhone = '0812'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT);

        return match ($scenario) {
            'vip_global_mixed_levels' => (function () use ($districts, $universities, $schoolCatalog, $fallbackName, $fallbackPhone, $index) {
                $district = $districts->random();
                $level = ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)];

                if ($level === 'UMUM') {
                    return $this->buildUmumPayload(
                        districtId: $district->id,
                        edition: 'vip',
                        name: "{$fallbackName} VIP",
                        phoneNumber: $fallbackPhone,
                        university: $this->pickUniversity($universities, $index),
                        includePersonalDocs: true,
                    );
                }

                return [
                    'district_id' => $district->id,
                    'education_level' => $level,
                    'edition' => 'vip',
                    'name' => "{$fallbackName} VIP",
                    'phone_number' => $fallbackPhone,
                    'school_name' => $this->pickCatalogSchool(
                        schoolCatalog: $schoolCatalog,
                        districtId: $district->id,
                        educationLevel: $level,
                        fallback: self::REGULER_SCHOOL_POOL[array_search($level, ['SD', 'SMP', 'SMA'], true) ?: 0],
                        index: $index,
                    ),
                ];
            })(),
            'reguler_school_cluster' => (function () use ($targetDistrict, $schoolCatalog, $fallbackName, $fallbackPhone, $index) {
                $level = ['SD', 'SMP', 'SMA'][random_int(0, 2)];

                return [
                    'district_id' => $targetDistrict->id,
                    'education_level' => $level,
                    'edition' => 'reguler',
                    'name' => "{$fallbackName} REG-CLUSTER",
                    'phone_number' => $fallbackPhone,
                    'school_name' => $this->pickCatalogSchool(
                        schoolCatalog: $schoolCatalog,
                        districtId: $targetDistrict->id,
                        educationLevel: $level,
                        fallback: self::REGULER_SCHOOL_POOL[array_search($level, ['SD', 'SMP', 'SMA'], true) ?: 0],
                        index: $index % 3,
                    ),
                ];
            })(),
            'reguler_target_surge' => (function () use ($targetDistrict, $schoolCatalog, $fallbackName, $fallbackPhone) {
                $level = $this->pickPreferredRegularLevel($schoolCatalog, $targetDistrict->id);

                return [
                    'district_id' => $targetDistrict->id,
                    'education_level' => $level,
                    'edition' => 'reguler',
                    'name' => "{$fallbackName} REG-SURGE",
                    'phone_number' => $fallbackPhone,
                    'school_name' => $this->pickCatalogSchool(
                        schoolCatalog: $schoolCatalog,
                        districtId: $targetDistrict->id,
                        educationLevel: $level,
                        fallback: self::REGULER_SCHOOL_POOL[array_search($level, ['SD', 'SMP', 'SMA'], true) ?: 0],
                        index: 0,
                    ),
                ];
            })(),
            'reguler_kota_spread' => (function () use ($targetDistrict, $sameKotaDistricts, $schoolCatalog, $fallbackName, $fallbackPhone, $index) {
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
                    'school_name' => $this->pickCatalogSchool(
                        schoolCatalog: $schoolCatalog,
                        districtId: $district->id,
                        educationLevel: $level,
                        fallback: "Sekolah Spread {$level}#".(($index % 7) + 1),
                        index: $index,
                    ),
                ];
            })(),
            'reguler_umum_with_docs' => $this->buildUmumPayload(
                districtId: $districts->random()->id,
                edition: 'reguler',
                name: "{$fallbackName} REG-UMUM",
                phoneNumber: $fallbackPhone,
                university: $this->pickUniversity($universities, $index),
                includePersonalDocs: true,
            ),
            'invalid_umum_without_docs' => $this->buildUmumPayload(
                districtId: $districts->random()->id,
                edition: random_int(0, 1) === 1 ? 'vip' : 'reguler',
                name: "{$fallbackName} INVALID-UMUM",
                phoneNumber: $fallbackPhone,
                university: $this->pickUniversity($universities, $index),
                includePersonalDocs: false,
            ),
            'invalid_district' => [
                'district_id' => $invalidDistrictId,
                'education_level' => 'SMA',
                'edition' => 'reguler',
                'name' => "{$fallbackName} INVALID-DIST",
                'phone_number' => $fallbackPhone,
                'school_name' => 'Sekolah Invalid District',
            ],
            'edge_case_identity' => (function () use ($districts, $universities, $schoolCatalog, $index) {
                $district = $districts->random();
                $level = ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)];
                $edition = random_int(0, 1) === 1 ? 'vip' : 'reguler';
                $phone = '0812'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT);

                if ($level === 'UMUM') {
                    $university = $this->pickUniversity($universities, $index);

                    return [
                        'district_id' => $district->id,
                        'education_level' => 'UMUM',
                        'edition' => $edition,
                        'name' => "QA {$index} ".fake()->name(),
                        'phone_number' => $phone,
                        'school_name' => $university->name,
                        'university_id' => $university->id,
                        'nik' => self::generateNik(),
                        'address' => 'Jl. Edge#'.random_int(1, 999),
                    ];
                }

                $schoolName = $this->pickCatalogSchool(
                    schoolCatalog: $schoolCatalog,
                    districtId: $district->id,
                    educationLevel: $level,
                    fallback: 'SMK-Al_Hikmah#'.random_int(100, 999),
                    index: $index,
                );

                return [
                    'district_id' => $district->id,
                    'education_level' => $level,
                    'edition' => $edition,
                    'name' => "QA {$index} ".fake()->name(),
                    'phone_number' => $phone,
                    'school_name' => random_int(0, 1) === 1 ? $schoolName : mb_strtolower($schoolName),
                ];
            })(),
            default => (function () use ($districts, $universities, $schoolCatalog, $fallbackName, $fallbackPhone, $index) {
                $district = $districts->random();
                $level = ['SD', 'SMP', 'SMA', 'UMUM'][random_int(0, 3)];
                $edition = random_int(1, 100) <= 35 ? 'vip' : 'reguler';

                if ($level === 'UMUM') {
                    return $this->buildUmumPayload(
                        districtId: $district->id,
                        edition: $edition,
                        name: "{$fallbackName} CITY",
                        phoneNumber: $fallbackPhone,
                        university: $this->pickUniversity($universities, $index),
                        includePersonalDocs: true,
                    );
                }

                return [
                    'district_id' => $district->id,
                    'education_level' => $level,
                    'edition' => $edition,
                    'name' => "{$fallbackName} CITY",
                    'phone_number' => $fallbackPhone,
                    'school_name' => $this->pickCatalogSchool(
                        schoolCatalog: $schoolCatalog,
                        districtId: $district->id,
                        educationLevel: $level,
                        fallback: 'Sekolah Kota '.random_int(1, 200),
                        index: $index,
                    ),
                ];
            })(),
        };
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    private function buildSchoolCatalog(): Collection
    {
        return SchoolSuggestion::query()
            ->orderBy('district_id')
            ->orderBy('education_level')
            ->orderBy('name')
            ->get(['district_id', 'education_level', 'name'])
            ->groupBy(fn (SchoolSuggestion $suggestion): string => $this->schoolCatalogKey(
                (int) $suggestion->district_id,
                (string) $suggestion->education_level,
            ))
            ->map(fn (Collection $group): Collection => $group
                ->pluck('name')
                ->filter()
                ->unique()
                ->values());
    }

    private function schoolCatalogKey(int $districtId, string $educationLevel): string
    {
        return $districtId.'|'.strtoupper($educationLevel);
    }

    /**
     * @param  Collection<string, Collection<int, string>>  $schoolCatalog
     */
    private function pickPreferredRegularLevel(Collection $schoolCatalog, int $districtId): string
    {
        foreach (['SMA', 'SMP', 'SD'] as $level) {
            if ($schoolCatalog->has($this->schoolCatalogKey($districtId, $level))) {
                return $level;
            }
        }

        return 'SMA';
    }

    /**
     * @param  Collection<string, Collection<int, string>>  $schoolCatalog
     */
    private function pickCatalogSchool(
        Collection $schoolCatalog,
        int $districtId,
        string $educationLevel,
        string $fallback,
        int $index = 0,
    ): string {
        /** @var Collection<int, string> $options */
        $options = $schoolCatalog->get(
            $this->schoolCatalogKey($districtId, $educationLevel),
            collect(),
        );

        if ($options->isEmpty()) {
            return $fallback;
        }

        return (string) ($options->values()->get($index % $options->count()) ?? $options->first());
    }

    /**
     * @param  Collection<int, University>  $universities
     */
    private function pickUniversity(Collection $universities, int $index): University
    {
        return $universities->values()->get($index % $universities->count()) ?? $universities->firstOrFail();
    }

    /**
     * @return array<string, int|string|null>
     */
    private function buildUmumPayload(
        int $districtId,
        string $edition,
        string $name,
        string $phoneNumber,
        University $university,
        bool $includePersonalDocs,
    ): array {
        return [
            'district_id' => $districtId,
            'education_level' => 'UMUM',
            'edition' => $edition,
            'name' => $name,
            'phone_number' => $phoneNumber,
            'school_name' => $university->name,
            'university_id' => $university->id,
            'nik' => $includePersonalDocs ? self::generateNik() : null,
            'address' => $includePersonalDocs ? 'Jl. Umum Tester No. '.random_int(1, 999) : null,
        ];
    }

    /**
     * @param  Collection<int, District>  $districts
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
     * @param  array<string, mixed>  $stats
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
     * @param  array<string, mixed>  $stats
     * @param  array<string, int|string|null>  $payload
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
     * @param  array<string, int>  $distribution
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
     * @param  array<string, mixed>  $stats
     */
    private function renderStats(array $stats, District $targetDistrict): void
    {
        $liveFullBatches = Batch::query()
            ->withActiveRegistrationsCount()
            ->get();
        $liveFullCount = $liveFullBatches
            ->filter(fn (Batch $batch): bool => $batch->isFullByOccupancy((int) ($batch->registrations_count ?? 0)))
            ->count();
        $staleFlagCount = $liveFullBatches
            ->filter(fn (Batch $batch): bool => $batch->is_full !== $batch->isFullByOccupancy((int) ($batch->registrations_count ?? 0)))
            ->count();

        $this->info('Testing selesai.');
        $this->line("Total Attempt          : <fg=cyan>{$stats['attempted']}</>");
        $this->line("Success                : <fg=green>{$stats['success']}</>");
        $this->line("Failed                 : <fg=red>{$stats['failed']}</>");
        $this->line("Expected Failures      : <fg=yellow>{$stats['expected_failures']}</>");
        $this->line("Unexpected Failures    : <fg=red>{$stats['unexpected_failures']}</>");
        $this->line("Unexpected Successes   : <fg=red>{$stats['unexpected_successes']}</>");
        $this->line("Batch Penuh (live)     : <fg=green>{$liveFullCount}</>");
        $this->line("Flag is_full stale     : <fg=yellow>{$staleFlagCount}</>");
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
                $isFull = $batch->isFullByOccupancy($regCount);
                $statusColor = $isFull ? 'green' : 'yellow';
                $statusText = $isFull ? '[PENUH]' : "[TERISI {$regCount}/".RegistrationService::BATCH_CAPACITY.']';
                $districtCol = $batch->district_id === null ? 'NULL (global)' : (string) $batch->district_id;
                $levelCol = $batch->education_level ?? 'NULL (mixed)';
                $this->line(
                    "- Jilid {$batch->batch_number} (district={$districtCol}, level={$levelCol}): "
                    ."<fg={$statusColor}>{$statusText}</> -> {$batch->name}"
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
                $isFull = $batch->isFullByOccupancy($regCount);
                $statusColor = $isFull ? 'green' : 'yellow';
                $statusText = $isFull ? '[PENUH]' : "[TERISI {$regCount}/".RegistrationService::BATCH_CAPACITY.']';
                $this->line(
                    "- Jilid {$batch->batch_number} ({$batch->education_level}): "
                    ."<fg={$statusColor}>{$statusText}</> | sekolah berbeda: {$distinctSchool} | "
                    ."kecamatan berbeda: {$distinctDistrict} -> {$batch->name}"
                );
            }
        }

        $this->newLine();
        $this->info('Cek dashboard Filament untuk validasi visual akhir.');
    }
}
