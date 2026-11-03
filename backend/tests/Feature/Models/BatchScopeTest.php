<?php

use App\Models\Batch;
use App\Models\District;

it('clears education and institution scope for global vip batches', function () {
    $batch = Batch::query()->create([
        'district_id' => null,
        'name' => 'Mushaf VIP Jakarta 9 (GOR)',
        'batch_number' => 'V9',
        'education_level' => 'UMUM',
        'institution_type' => 'MASJID',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    expect($batch->fresh()->education_level)->toBeNull()
        ->and($batch->fresh()->institution_type)->toBeNull();
});

it('forces non-school district batches to the umum education level', function () {
    $district = District::query()->create([
        'name' => 'Kebayoran Baru',
        'code' => '3171060',
    ]);

    $batch = Batch::query()->create([
        'district_id' => $district->id,
        'name' => 'Mushaf Reguler MASJID Kebayoran Baru 1',
        'batch_number' => '1',
        'education_level' => 'SD',
        'institution_type' => 'MASJID',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    expect($batch->fresh()->education_level)->toBe('UMUM')
        ->and($batch->fresh()->institution_type)->toBe('MASJID');
});
