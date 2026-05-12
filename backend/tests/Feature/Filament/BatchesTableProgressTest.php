<?php

use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;

test('batch progress filters follow live occupancy bands', function () {
    $district = District::query()->create([
        'name' => 'Tanjung Priok',
        'code' => '3172050',
    ]);

    $fullBatch = makeBatchWithCapacity($district, '1', 10);
    $highBatch = makeBatchWithCapacity($district, '2', 10);
    $lowBatch = makeBatchWithCapacity($district, '3', 10);

    fillBatchForProgress($fullBatch, $district, 10);
    fillBatchForProgress($highBatch, $district, 8);
    fillBatchForProgress($lowBatch, $district, 3);

    expect(Batch::query()->whereFullByOccupancy(true)->pluck('batch_number')->all())
        ->toBe(['1'])
        ->and(Batch::query()->whereOccupancyBetween(0.8, 1.0)->pluck('batch_number')->all())
        ->toBe(['2'])
        ->and(Batch::query()->whereOccupancyBetween(null, 0.8)->pluck('batch_number')->all())
        ->toBe(['3']);
});

test('batch progress sorting uses occupancy ratio instead of raw registration totals', function () {
    $district = District::query()->create([
        'name' => 'Johar Baru',
        'code' => '3171040',
    ]);

    $ratioFifty = makeBatchWithCapacity($district, '1', 20);
    $ratioEighty = makeBatchWithCapacity($district, '2', 10);
    $ratioFull = makeBatchWithCapacity($district, '3', 10);

    fillBatchForProgress($ratioFifty, $district, 10);
    fillBatchForProgress($ratioEighty, $district, 8);
    fillBatchForProgress($ratioFull, $district, 10);

    expect(Batch::query()->orderByOccupancy('desc')->pluck('batch_number')->all())
        ->toBe(['3', '2', '1'])
        ->and(Batch::query()->orderByOccupancy('asc')->pluck('batch_number')->all())
        ->toBe(['1', '2', '3']);
});

function makeBatchWithCapacity(District $district, string $batchNumber, int $maxCapacity): Batch
{
    return Batch::query()->create([
        'name' => "Mushaf Reguler SMP {$district->name} {$batchNumber}",
        'district_id' => $district->id,
        'batch_number' => $batchNumber,
        'education_level' => 'SMP',
        'max_capacity' => $maxCapacity,
        'is_full' => false,
    ]);
}

function fillBatchForProgress(Batch $batch, District $district, int $count): void
{
    $rows = [];
    $now = now();

    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'batch_id' => $batch->id,
            'district_id' => $district->id,
            'education_level' => 'SMP',
            'name' => 'Progress Filler '.$batch->id.'-'.$i,
            'phone_number' => '628700000'.str_pad((string) ($batch->id * 1000 + $i), 5, '0', STR_PAD_LEFT),
            'edition' => 'reguler',
            'school_name' => 'SMPN Progress',
            'registration_code' => 'PROGRESS-'.$batch->id.'-'.$i,
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
