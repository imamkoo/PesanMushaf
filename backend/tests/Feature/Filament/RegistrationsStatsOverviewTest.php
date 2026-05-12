<?php

use App\Filament\Widgets\RegistrationsStatsOverview;
use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Services\RegistrationService;
use Filament\Widgets\StatsOverviewWidget\Stat;

test('batch penuh stat follows live occupancy instead of stale flags', function () {
    $district = District::query()->create([
        'name' => 'Sawah Besar',
        'code' => '3171020',
    ]);

    $fullBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMA Sawah Besar 1',
        'district_id' => $district->id,
        'batch_number' => '1',
        'education_level' => 'SMA',
        'max_capacity' => RegistrationService::BATCH_CAPACITY,
        'is_full' => false,
    ]);

    $openBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMA Sawah Besar 2',
        'district_id' => $district->id,
        'batch_number' => '2',
        'education_level' => 'SMA',
        'max_capacity' => RegistrationService::BATCH_CAPACITY,
        'is_full' => true,
    ]);

    fillBatchForStats($fullBatch, $district, RegistrationService::BATCH_CAPACITY);
    fillBatchForStats($openBatch, $district, RegistrationService::BATCH_CAPACITY - 1);

    $widget = new class extends RegistrationsStatsOverview
    {
        /**
         * @return array<int, Stat>
         */
        public function exposeStats(): array
        {
            return $this->getStats();
        }
    };

    $batchFullStat = collect($widget->exposeStats())
        ->first(fn (Stat $stat) => $stat->getLabel() === 'Batch Penuh');

    expect($batchFullStat)->not->toBeNull()
        ->and((string) $batchFullStat->getValue())->toBe('1');
});

test('stats overview keeps umum and non-umum batch metrics without global null card', function () {
    $district = District::query()->create([
        'name' => 'Kemayoran',
        'code' => '3171070',
    ]);

    $umumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler UMUM Kemayoran 1',
        'district_id' => $district->id,
        'batch_number' => '10',
        'education_level' => 'UMUM',
        'max_capacity' => 20,
        'is_full' => false,
    ]);

    $nonUmumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMA Kemayoran 1',
        'district_id' => $district->id,
        'batch_number' => '11',
        'education_level' => 'SMA',
        'max_capacity' => 20,
        'is_full' => false,
    ]);

    Batch::query()->create([
        'name' => 'Mushaf VIP Jakarta Global 1',
        'district_id' => null,
        'batch_number' => '12',
        'education_level' => null,
        'max_capacity' => 20,
        'is_full' => false,
    ]);

    fillBatchForStats($umumBatch, $district, 2, 'UMUM');
    fillBatchForStats($nonUmumBatch, $district, 3, 'SMA');

    $widget = new class extends RegistrationsStatsOverview
    {
        /**
         * @return array<int, Stat>
         */
        public function exposeStats(): array
        {
            return $this->getStats();
        }
    };

    $stats = collect($widget->exposeStats())
        ->mapWithKeys(fn (Stat $stat): array => [$stat->getLabel() => (string) $stat->getValue()]);

    expect($stats->get('Pendaftar UMUM'))->toBe('2')
        ->and($stats->get('Pendaftar Non-UMUM'))->toBe('3')
        ->and($stats->get('Batch UMUM'))->toBe('1')
        ->and($stats->get('Batch Non-UMUM'))->toBe('1')
        ->and($stats->has('Batch Global / Null'))->toBeFalse();
});

function fillBatchForStats(Batch $batch, District $district, int $count, string $educationLevel = 'SMA'): void
{
    $rows = [];
    $now = now();

    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'batch_id' => $batch->id,
            'district_id' => $district->id,
            'education_level' => $educationLevel,
            'name' => 'Stats Filler '.$batch->id.'-'.$i,
            'phone_number' => '628600000'.str_pad((string) ($batch->id * 1000 + $i), 5, '0', STR_PAD_LEFT),
            'edition' => 'reguler',
            'school_name' => 'SMAN Statistik',
            'registration_code' => 'STAT-'.$batch->id.'-'.$i,
            'page_number' => $i + 1,
            'base_price' => 10000,
            'total_payment' => 10000,
            'payment_status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    Registration::query()->insert($rows);
}
