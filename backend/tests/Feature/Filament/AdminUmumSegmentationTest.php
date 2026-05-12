<?php

use App\Filament\Widgets\BatchCapacityChartWidget;
use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;

test('admin segmentation scopes keep umum non-umum and legacy data apart', function () {
    $district = District::query()->create([
        'name' => 'Cempaka Putih',
        'code' => '3171060',
    ]);

    $umumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler UMUM Cempaka Putih 1',
        'district_id' => $district->id,
        'batch_number' => '21',
        'education_level' => 'UMUM',
        'max_capacity' => 10,
        'is_full' => false,
    ]);

    $nonUmumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMP Cempaka Putih 1',
        'district_id' => $district->id,
        'batch_number' => '22',
        'education_level' => 'SMP',
        'max_capacity' => 10,
        'is_full' => false,
    ]);

    $globalBatch = Batch::query()->create([
        'name' => 'Mushaf VIP Global 1',
        'district_id' => null,
        'batch_number' => '23',
        'education_level' => null,
        'max_capacity' => 10,
        'is_full' => false,
    ]);

    insertAdminSegmentationRegistration($district, $umumBatch, 'UMUM', 'Peserta Umum');
    insertAdminSegmentationRegistration($district, $nonUmumBatch, 'SMP', 'Peserta SMP');
    insertAdminSegmentationRegistration($district, $globalBatch, null, 'Peserta Legacy');

    expect(Registration::query()->whereUmum()->pluck('name')->all())
        ->toBe(['Peserta Umum'])
        ->and(Registration::query()->whereNonUmum()->pluck('name')->all())
        ->toBe(['Peserta SMP'])
        ->and(Registration::query()->whereLegacyOrUnknownLevel()->pluck('name')->all())
        ->toBe(['Peserta Legacy'])
        ->and(Batch::query()->whereUmum()->pluck('batch_number')->all())
        ->toBe(['21'])
        ->and(Batch::query()->whereNonUmum()->pluck('batch_number')->all())
        ->toBe(['22'])
        ->and(Batch::query()->whereGlobalOrNull()->pluck('batch_number')->all())
        ->toBe(['23']);
});

test('batch capacity chart returns sd smp sma and umum labels only', function () {
    $district = District::query()->create([
        'name' => 'Menteng',
        'code' => '3171030',
    ]);

    $umumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler UMUM Menteng 1',
        'district_id' => $district->id,
        'batch_number' => '31',
        'education_level' => 'UMUM',
        'max_capacity' => 30,
        'is_full' => false,
    ]);

    $nonUmumBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMA Menteng 1',
        'district_id' => $district->id,
        'batch_number' => '32',
        'education_level' => 'SMA',
        'max_capacity' => 40,
        'is_full' => false,
    ]);

    $globalBatch = Batch::query()->create([
        'name' => 'Mushaf VIP Global 2',
        'district_id' => null,
        'batch_number' => '33',
        'education_level' => null,
        'max_capacity' => 50,
        'is_full' => false,
    ]);

    fillBatchForAdminSegmentation($umumBatch, $district, 6, 'UMUM');
    fillBatchForAdminSegmentation($nonUmumBatch, $district, 8, 'SMA');
    fillBatchForAdminSegmentation($globalBatch, $district, 4, null);

    Batch::query()->create([
        'name' => 'Mushaf Reguler SD Menteng 1',
        'district_id' => $district->id,
        'batch_number' => '34',
        'education_level' => 'SD',
        'max_capacity' => 15,
        'is_full' => false,
    ]);

    $smpBatch = Batch::query()->create([
        'name' => 'Mushaf Reguler SMP Menteng 1',
        'district_id' => $district->id,
        'batch_number' => '35',
        'education_level' => 'SMP',
        'max_capacity' => 25,
        'is_full' => false,
    ]);

    fillBatchForAdminSegmentation($smpBatch, $district, 5, 'SMP');

    $widget = new class extends BatchCapacityChartWidget
    {
        public function exposeData(): array
        {
            return $this->getData();
        }
    };

    $data = $widget->exposeData();
    $datasets = collect($data['datasets'])
        ->mapWithKeys(fn (array $dataset): array => [$dataset['label'] => $dataset['data']]);

    expect($data['labels'])->toBe(['SD', 'SMP', 'SMA', 'UMUM'])
        ->and($datasets->get('Kapasitas'))->toBe([15, 25, 40, 30])
        ->and($datasets->get('Terisi'))->toBe([0, 5, 8, 6]);
});

function fillBatchForAdminSegmentation(Batch $batch, District $district, int $count, ?string $educationLevel): void
{
    for ($i = 0; $i < $count; $i++) {
        insertAdminSegmentationRegistration($district, $batch, $educationLevel, 'Segmentation-'.$batch->id.'-'.$i, $i + 1);
    }
}

function insertAdminSegmentationRegistration(
    District $district,
    Batch $batch,
    ?string $educationLevel,
    string $name,
    int $pageNumber = 1,
): void {
    Registration::query()->create([
        'batch_id' => $batch->id,
        'district_id' => $district->id,
        'education_level' => $educationLevel,
        'name' => $name,
        'phone_number' => '628800000'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        'edition' => $batch->district_id === null ? 'vip' : 'reguler',
        'school_name' => $educationLevel === 'UMUM' ? 'Universitas Segmentasi' : 'Sekolah Segmentasi',
        'registration_code' => 'ADM-'.$batch->id.'-'.$pageNumber.'-'.str()->random(5),
        'page_number' => $pageNumber,
        'base_price' => 10000,
        'total_payment' => 10000,
        'payment_status' => 'pending',
    ]);
}
