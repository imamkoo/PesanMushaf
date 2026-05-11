<?php

use App\Http\Resources\Api\BatchResource;
use App\Http\Resources\Api\DistrictResource;
use App\Http\Resources\Api\RegistrationResource;
use App\Http\Resources\Api\RegistrationStatusResource;
use App\Http\Resources\Api\UniversityResource;
use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Models\University;
use Illuminate\Http\Request;

test('district resource maps expected fields', function () {
    $district = new District([
        'name' => 'Kebayoran Baru',
        'slug' => 'kebayoran-baru',
        'code' => '3171',
        'photo' => 'district.jpg',
    ]);
    $district->id = 10;

    $data = DistrictResource::make($district)->toArray(new Request());

    expect($data)->toMatchArray([
        'id' => 10,
        'name' => 'Kebayoran Baru',
        'slug' => 'kebayoran-baru',
        'code' => '3171',
        'photo' => 'district.jpg',
    ]);
});

test('university resource maps expected fields', function () {
    $university = new University([
        'name' => 'Universitas Indonesia',
        'type' => 'PTN',
        'city' => 'Depok',
    ]);
    $university->id = 11;

    $data = UniversityResource::make($university)->toArray(new Request());

    expect($data)->toMatchArray([
        'id' => 11,
        'name' => 'Universitas Indonesia',
        'type' => 'PTN',
        'city' => 'Depok',
    ]);
});

test('batch resource includes district relation when loaded', function () {
    $district = new District([
        'name' => 'Tebet',
        'slug' => 'tebet',
        'code' => '3172',
    ]);
    $district->id = 20;

    $batch = new Batch([
        'district_id' => 20,
        'name' => 'Mushaf Reguler SMP Tebet 1',
        'slug' => 'mushaf-reguler-smp-tebet-1',
        'batch_number' => '1',
        'education_level' => 'SMP',
        'max_capacity' => 603,
        'is_full' => false,
    ]);
    $batch->id = 21;
    $batch->setRelation('district', $district);

    $data = BatchResource::make($batch)->toArray(new Request());

    expect($data)->toMatchArray([
        'id' => 21,
        'district_id' => 20,
        'name' => 'Mushaf Reguler SMP Tebet 1',
        'education_level' => 'SMP',
        'is_full' => false,
    ])->and($data['district']['name'])->toBe('Tebet');
});

test('registration resource includes financial and nested relations', function () {
    $district = new District([
        'name' => 'Mampang',
        'slug' => 'mampang',
        'code' => '3173',
    ]);
    $district->id = 30;

    $batch = new Batch([
        'district_id' => 30,
        'name' => 'Mushaf VIP SMA Mampang 1 (GOR)',
        'slug' => 'mushaf-vip-sma-mampang-1-gor',
        'batch_number' => '1',
        'education_level' => 'SMA',
        'max_capacity' => 603,
        'is_full' => false,
    ]);
    $batch->id = 31;

    $registration = new Registration([
        'batch_id' => 31,
        'district_id' => 30,
        'education_level' => 'SMA',
        'edition' => 'vip',
        'name' => 'Budi',
        'phone_number' => '08123456789',
        'school_name' => 'SMAN 1',
        'registration_code' => '3173-VIP-SMA-MUSHAF1-001-SMAN1-ABCD',
        'page_number' => 1,
        'base_price' => 50000,
        'total_payment' => 50000,
        'payment_status' => 'pending',
    ]);
    $registration->id = 32;
    $registration->setRelation('district', $district);
    $registration->setRelation('batch', $batch);

    $data = RegistrationResource::make($registration)->toArray(new Request());

    expect($data)->toMatchArray([
        'id' => 32,
        'registration_code' => '3173-VIP-SMA-MUSHAF1-001-SMAN1-ABCD',
        'name' => 'Budi',
        'status' => 'pending',
        'financial' => [
            'base_price' => 50000,
            'total_payment' => 50000,
        ],
    ])->and($data['district']['name'])->toBe('Mampang')
        ->and($data['batch']['name'])->toBe('Mushaf VIP SMA Mampang 1 (GOR)');
});

test('registration status resource returns compact status payload', function () {
    $registration = new Registration([
        'registration_code' => '3172-REGULER-SMP-MUSHAF1-010-SMPN1-AAAA',
        'name' => 'Siti',
        'school_name' => 'SMPN 1',
        'edition' => 'reguler',
        'education_level' => 'SMP',
        'page_number' => 10,
        'payment_status' => 'success',
        'total_payment' => 12000,
    ]);
    $registration->id = 33;

    $data = RegistrationStatusResource::make($registration)->toArray(new Request());

    expect($data)->toMatchArray([
        'id' => 33,
        'registration_code' => '3172-REGULER-SMP-MUSHAF1-010-SMPN1-AAAA',
        'name' => 'Siti',
        'status' => 'success',
        'total_payment' => 12000,
    ]);
});
