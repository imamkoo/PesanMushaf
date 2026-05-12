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

function fillBatchForStats(Batch $batch, District $district, int $count): void
{
    $rows = [];
    $now = now();

    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'batch_id' => $batch->id,
            'district_id' => $district->id,
            'education_level' => 'SMA',
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
