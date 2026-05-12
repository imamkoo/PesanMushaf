<?php

use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Models\SchoolSuggestion;
use App\Models\University;
use App\Services\RegistrationService;

test('master stress test command runs and prints scenario report', function () {
    District::create([
        'name' => 'Kecamatan Uji',
        'code' => 'KC001',
    ]);

    foreach (['SD', 'SMP', 'SMA'] as $level) {
        SchoolSuggestion::create([
            'district_id' => 1,
            'education_level' => $level,
            'name' => "{$level} Uji 1",
        ]);
    }

    University::create([
        'name' => 'Universitas Uji',
        'type' => 'PTN',
        'city' => 'Jakarta',
    ]);

    $this->artisan('app:master-stress-test', ['count' => 5])
        ->expectsOutputToContain('Testing selesai.')
        ->expectsOutputToContain('Breakdown per skenario:')
        ->assertSuccessful();
});

test('master stress test can fill two VIP batches and open V3 deterministically', function () {
    $district = District::create([
        'name' => 'Kecamatan Uji',
        'code' => '3171030',
    ]);

    foreach (['SD', 'SMP', 'SMA'] as $level) {
        SchoolSuggestion::create([
            'district_id' => $district->id,
            'education_level' => $level,
            'name' => "{$level} Uji 1",
        ]);
    }

    University::create([
        'name' => 'Universitas Uji 1',
        'type' => 'PTN',
        'city' => 'Jakarta',
    ]);

    University::create([
        'name' => 'Universitas Uji 2',
        'type' => 'PTS',
        'city' => 'Jakarta',
    ]);

    $vipOne = Batch::create([
        'name' => 'Mushaf VIP Jakarta 1 (GOR)',
        'district_id' => null,
        'batch_number' => 'V1',
        'education_level' => null,
        'max_capacity' => RegistrationService::BATCH_CAPACITY,
        'is_full' => false,
    ]);

    $vipTwo = Batch::create([
        'name' => 'Mushaf VIP Jakarta 2 (GOR)',
        'district_id' => null,
        'batch_number' => 'V2',
        'education_level' => null,
        'max_capacity' => RegistrationService::BATCH_CAPACITY,
        'is_full' => false,
    ]);

    fillVipBatchToOneRemaining($vipOne, $district);
    fillVipBatchToOneRemaining($vipTwo, $district);

    $this->artisan('app:master-stress-test', ['count' => 3])
        ->expectsOutputToContain('Batch Penuh (live)')
        ->assertSuccessful();

    expect(Batch::query()->where('name', 'like', 'Mushaf VIP Jakarta%')->count())->toBe(3)
        ->and(Batch::query()->whereFullByOccupancy(true)->count())->toBe(2)
        ->and(Batch::query()->where('batch_number', 'V3')->exists())->toBeTrue();
});

function fillVipBatchToOneRemaining(Batch $batch, District $district): void
{
    $rows = [];
    $now = now();

    for ($i = 0; $i < RegistrationService::BATCH_CAPACITY - 1; $i++) {
        $rows[] = [
            'batch_id' => $batch->id,
            'district_id' => $district->id,
            'education_level' => 'SMA',
            'name' => 'VIP Prefill '.$batch->id.'-'.$i,
            'phone_number' => '628500000'.str_pad((string) ($batch->id * 1000 + $i), 5, '0', STR_PAD_LEFT),
            'edition' => 'vip',
            'school_name' => 'SMAN Uji 1',
            'registration_code' => 'VIP-PREFILL-'.$batch->id.'-'.$i,
            'page_number' => $i + 1,
            'base_price' => 20000,
            'total_payment' => 20000,
            'payment_status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    Registration::query()->insert($rows);
}
