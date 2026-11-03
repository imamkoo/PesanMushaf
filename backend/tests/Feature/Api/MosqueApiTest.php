<?php

use App\Models\District;
use App\Models\Mosque;

it('searches active mosques and never exposes phone numbers', function () {
    $district = District::query()->create([
        'name' => 'Sawah Besar',
        'code' => '3171070',
    ]);

    $mosque = Mosque::query()->create([
        'dmi_code' => 'M-1417',
        'name' => 'Masjid Istiqlal',
        'address' => 'Jl. Taman Wijaya Kusuma',
        'district_name' => 'Sawah Besar',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
        'phone' => '021-123456',
        'source' => 'dmi',
        'is_verified' => true,
        'is_active' => true,
    ]);

    Mosque::query()->create([
        'dmi_code' => 'M-9999',
        'name' => 'Masjid Istiqlal Nonaktif',
        'district_name' => 'Sawah Besar',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
        'source' => 'dmi',
        'is_verified' => true,
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/mosques?q=istiqlal&district_id='.$district->id.'&limit=10')
        ->assertOk()
        ->assertJsonPath('data.0.id', $mosque->id)
        ->assertJsonPath('data.0.name', 'Masjid Istiqlal')
        ->assertJsonPath('data.0.district_name', 'Sawah Besar')
        ->assertJsonPath('meta.count', 1);

    expect($response->json('data.0'))->not->toHaveKey('phone');
});

it('filters mosques by district case-insensitively', function () {
    $district = District::query()->create([
        'name' => 'Kebayoran Baru',
        'code' => '3171060',
    ]);

    $matching = Mosque::query()->create([
        'name' => 'Masjid Kebayoran',
        'district_name' => 'kebayoran baru',
        'city' => 'Jakarta Selatan',
        'province' => 'DKI Jakarta',
        'source' => 'manual',
        'is_active' => true,
    ]);

    Mosque::query()->create([
        'name' => 'Masjid Lain',
        'district_name' => 'Gambir',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
        'source' => 'manual',
        'is_active' => true,
    ]);

    $this->getJson('/api/mosques?district_id='.$district->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);
});

it('shows an active mosque without its internal phone number', function () {
    $mosque = Mosque::query()->create([
        'name' => 'Masjid Al-Azhar',
        'phone' => '021-7654321',
        'source' => 'manual',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/mosques/'.$mosque->id)
        ->assertOk()
        ->assertJsonPath('data.id', $mosque->id)
        ->assertJsonPath('data.name', 'Masjid Al-Azhar');

    expect($response->json('data'))->not->toHaveKey('phone');
});

it('returns not found for an inactive mosque', function () {
    $mosque = Mosque::query()->create([
        'name' => 'Masjid Lama',
        'source' => 'manual',
        'is_active' => false,
    ]);

    $this->getJson('/api/mosques/'.$mosque->id)->assertNotFound();
});
