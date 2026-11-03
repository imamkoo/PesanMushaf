<?php

use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Models\Batch;
use App\Models\District;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

it('requires education level and institution type for district batches', function () {
    $district = District::query()->create([
        'name' => 'Kebayoran Baru',
        'code' => '3171060',
    ]);

    Livewire::test(CreateBatch::class)
        ->fillForm([
            'district_id' => $district->id,
            'name' => 'Mushaf Reguler Masjid Kebayoran Baru 1',
            'batch_number' => '1',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'education_level' => 'required',
            'institution_type' => 'required',
        ]);
});

it('creates a district batch with the selected institution type', function () {
    $district = District::query()->create([
        'name' => 'Kebayoran Baru',
        'code' => '3171060',
    ]);

    Livewire::test(CreateBatch::class)
        ->fillForm([
            'district_id' => $district->id,
            'name' => 'Mushaf Reguler Masjid Kebayoran Baru 1',
            'batch_number' => '1',
            'education_level' => 'UMUM',
            'institution_type' => 'MASJID',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $batch = Batch::query()
        ->where('name', 'Mushaf Reguler Masjid Kebayoran Baru 1')
        ->firstOrFail();

    expect($batch->district_id)->toBe($district->id)
        ->and($batch->education_level)->toBe('UMUM')
        ->and($batch->institution_type)->toBe('MASJID');
});
