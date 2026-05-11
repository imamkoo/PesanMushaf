<?php

use App\Models\ApiKey;
use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Models\University;

function apiHeaders(): array
{
    return ['X-API-KEY' => 'test-api-key'];
}

beforeEach(function () {
    ApiKey::query()->create([
        'name' => 'Test Key',
        'key' => 'test-api-key',
    ]);
});

test('district endpoint returns collection data', function () {
    District::query()->create([
        'name' => 'Tebet',
        'code' => '3172',
    ]);

    $response = $this->getJson('/api/districts', apiHeaders());

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Tebet');
});

test('university endpoint returns collection data', function () {
    University::query()->create([
        'name' => 'Universitas Indonesia',
        'type' => 'PTN',
        'city' => 'Depok',
    ]);

    $response = $this->getJson('/api/universities', apiHeaders());

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Universitas Indonesia');
});

test('batch endpoint returns collection data with district', function () {
    $district = District::query()->create([
        'name' => 'Mampang',
        'code' => '3173',
    ]);

    Batch::query()->create([
        'district_id' => $district->id,
        'name' => 'Mushaf Reguler SMA Mampang 1',
        'batch_number' => '1',
        'education_level' => 'SMA',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    $response = $this->getJson('/api/batches', apiHeaders());

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.district.name', 'Mampang');
});

test('registration status endpoint returns status payload', function () {
    $district = District::query()->create([
        'name' => 'Kebayoran Baru',
        'code' => '3171',
    ]);

    $batch = Batch::query()->create([
        'district_id' => $district->id,
        'name' => 'Mushaf VIP SMP Kebayoran 1 (GOR)',
        'batch_number' => '1',
        'education_level' => 'SMP',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    Registration::query()->create([
        'batch_id' => $batch->id,
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'vip',
        'name' => 'Ahmad',
        'phone_number' => '0812999999',
        'school_name' => 'SMPN 5',
        'registration_code' => '3171-VIP-SMP-MUSHAF1-001-SMPN5-ZZZZ',
        'page_number' => 1,
        'base_price' => 50000,
        'total_payment' => 50000,
        'payment_status' => 'pending',
    ]);

    $response = $this->getJson('/api/registrations/3171-VIP-SMP-MUSHAF1-001-SMPN5-ZZZZ/status', apiHeaders());

    $response->assertSuccessful()
        ->assertJsonPath('data.0.registration_code', '3171-VIP-SMP-MUSHAF1-001-SMPN5-ZZZZ')
        ->assertJsonPath('data.0.status', 'pending');
});

test('register endpoint stores a registration and returns payload', function () {
    $district = District::query()->create([
        'name' => 'Setiabudi',
        'code' => '3174',
    ]);

    Batch::query()->create([
        'district_id' => $district->id,
        'name' => 'Mushaf Reguler SMA Setiabudi 1',
        'batch_number' => '1',
        'education_level' => 'SMA',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    $payload = [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Bima Putra',
        'phone_number' => '0812888888',
        'school_name' => 'SMAN 8 Jakarta',
        'email' => 'bima@example.com',
    ];

    $response = $this->postJson('/api/register', $payload, apiHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Bima Putra')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseCount('registrations', 1);
});

test('register endpoint returns validation errors for invalid payload', function () {
    $response = $this->postJson('/api/register', [
        'district_id' => 99999,
        'education_level' => 'KULIAH',
    ], apiHeaders());

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Data pendaftaran tidak valid.')
        ->assertJsonStructure([
            'errors' => ['district_id', 'education_level', 'edition', 'name', 'phone_number', 'school_name'],
        ]);
});

test('read-only catalog endpoints are reachable without an api key', function () {
    District::query()->create([
        'name' => 'Tebet',
        'code' => '3172',
    ]);

    $this->getJson('/api/districts')->assertSuccessful();
});

test('registration status endpoint returns not found for unknown code', function () {
    $response = $this->getJson('/api/registrations/UNKNOWN-CODE/status', apiHeaders());

    $response->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Kode pendaftaran atau nomor WhatsApp tidak ditemukan.');
});

