<?php

use App\Models\District;
use App\Models\University;

test('master stress test command runs and prints scenario report', function () {
    District::create([
        'name' => 'Kecamatan Uji',
        'code' => 'KC001',
    ]);

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
