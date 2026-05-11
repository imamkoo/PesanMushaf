<?php

use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Services\RegistrationService;

function makeServiceDistrict(string $name, string $code): District
{
    return District::query()->create([
        'name' => $name,
        'code' => $code,
    ]);
}

function makeNikDigits(): string
{
    $nik = '';
    for ($i = 0; $i < 16; $i++) {
        $nik .= (string) random_int(0, 9);
    }

    return $nik;
}

it('places same-school Reguler registrations in the same batch', function () {
    $district = makeServiceDistrict('Cilandak', '3171030');

    $first = RegistrationService::register([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Tester A',
        'phone_number' => '6281000099001',
        'school_name' => 'SMPN 1 Cilandak',
    ]);

    $second = RegistrationService::register([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Tester B',
        'phone_number' => '6281000099002',
        'school_name' => 'SMPN 1 Cilandak',
    ]);

    expect($first->batch_id)->toBe($second->batch_id);

    $batch = Batch::find($first->batch_id);
    expect($batch->batch_number)->toBe('1')
        ->and($batch->education_level)->toBe('SMP')
        ->and($batch->district_id)->toBe($district->id);
});

it('uses numeric incrementing batch_number even past 10 batches', function () {
    $district = makeServiceDistrict('Cilandak', '3171030');

    foreach (range(1, 11) as $n) {
        Batch::query()->create([
            'name' => "Mushaf SMP J{$n}",
            'slug' => "mushaf-smp-j{$n}",
            'district_id' => $district->id,
            'batch_number' => (string) $n,
            'education_level' => 'SMP',
            'max_capacity' => RegistrationService::BATCH_CAPACITY,
            'is_full' => true,
        ]);
    }

    $different = makeServiceDistrict('Pasar Rebo', '3172040'); // kota berbeda → batch baru

    $reg = RegistrationService::register([
        'district_id' => $different->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Tester X',
        'phone_number' => '6281000099100',
        'school_name' => 'SMPN 9 Pasar Rebo',
    ]);

    $batch = Batch::find($reg->batch_id);
    expect($batch->batch_number)->toBe('12')
        ->and((int) $batch->batch_number)->toBe(12);
});

it('places VIP registrations in a global batch with V-prefix and null scope', function () {
    $a = makeServiceDistrict('Cilandak', '3171030');
    $b = makeServiceDistrict('Cengkareng', '3174070');

    // VIP non-UMUM tidak perlu NIK / alamat (verifikasi via institusi sekolah).
    $first = RegistrationService::register([
        'district_id' => $a->id,
        'education_level' => 'SMA',
        'edition' => 'vip',
        'name' => 'VIP A',
        'phone_number' => '6281000099500',
        'school_name' => 'X-Inst',
    ]);

    // VIP UMUM tetap mengirim NIK + alamat sesuai kontrak baru.
    $second = RegistrationService::register([
        'district_id' => $b->id,
        'education_level' => 'UMUM',
        'edition' => 'vip',
        'name' => 'VIP B',
        'phone_number' => '6281000099501',
        'school_name' => 'Y-Inst',
        'nik' => makeNikDigits(),
        'address' => 'Jl. VIP 2',
    ]);

    expect($first->batch_id)->toBe($second->batch_id);

    $batch = Batch::find($first->batch_id);
    expect($batch->batch_number)->toBe('V1')
        ->and($batch->district_id)->toBeNull()
        ->and($batch->education_level)->toBeNull()
        ->and($batch->name)->toStartWith('Mushaf VIP Jakarta');
});

it('builds smart codes from registrant district and level even on kota extension', function () {
    $cilandak = makeServiceDistrict('Cilandak', '3171030');
    $pasarMinggu = makeServiceDistrict('Pasar Minggu', '3171040');

    RegistrationService::register([
        'district_id' => $cilandak->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Anchor',
        'phone_number' => '6281000099700',
        'school_name' => 'SMPN 1 Cilandak',
    ]);

    $extension = RegistrationService::register([
        'district_id' => $pasarMinggu->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Extension',
        'phone_number' => '6281000099701',
        'school_name' => 'SMPN 99 Pasar Minggu',
    ]);

    expect($extension->registration_code)->toStartWith('3171040-REGULER-SMP-');
});

it('marks the batch as full once capacity is reached', function () {
    $district = makeServiceDistrict('Cilandak', '3171030');

    $first = RegistrationService::register([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Anchor',
        'phone_number' => '6281000099900',
        'school_name' => 'SMPN 1 Cilandak',
    ]);

    $batchId = $first->batch_id;
    $needed = RegistrationService::BATCH_CAPACITY - 2;
    $now = now();
    $rows = [];
    for ($i = 0; $i < $needed; $i++) {
        $rows[] = [
            'batch_id' => $batchId,
            'district_id' => $district->id,
            'education_level' => 'SMP',
            'name' => 'Filler ' . $i,
            'phone_number' => '628400000' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'edition' => 'reguler',
            'school_name' => 'SMPN 1 Cilandak',
            'registration_code' => 'FILL-' . $i . '-' . random_int(1000, 9999),
            'page_number' => 2 + $i,
            'base_price' => 10000,
            'total_payment' => 10000,
            'payment_status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    Registration::query()->insert($rows);

    // Slot ke-603 menutup batch
    $closer = RegistrationService::register([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Closer',
        'phone_number' => '6281000099901',
        'school_name' => 'SMPN 1 Cilandak',
    ]);

    expect($closer->batch_id)->toBe($batchId)
        ->and($closer->page_number)->toBe(RegistrationService::BATCH_CAPACITY)
        ->and(Batch::find($batchId)->is_full)->toBeTrue();
});
