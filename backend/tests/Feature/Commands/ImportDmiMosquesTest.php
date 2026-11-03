<?php

use App\Models\District;
use App\Models\Mosque;
use Illuminate\Support\Facades\Http;

function dmiImportHtml(string $name = 'Masjid Contoh', string $address = 'Jl. Contoh No. 1', int $total = 2, string $region = 'Sawah Besar, Jakarta Pusat, DKI Jakarta'): string
{
    return <<<HTML
<html>
<body>
<p>Menampilkan 1 dari {$total} masjid</p>
<table class="masjid-dmi-table">
    <tbody>
        <tr>
            <td>M-1</td>
            <td>{$name}</td>
            <td>{$address}</td>
            <td>{$region}</td>
            <td>021 123456</td>
        </tr>
        <tr>
            <td>M-2</td>
            <td>Masjid Kedua</td>
            <td>Jl. Kedua No. 2</td>
            <td>Gambir, Jakarta Pusat, DKI Jakarta</td>
            <td></td>
        </tr>
    </tbody>
</table>
</body>
</html>
HTML;
}

it('imports dmi rows without storing phone numbers by default', function () {
    Http::fake([
        'https://dmi.or.id/*' => Http::response(dmiImportHtml(), 200),
    ]);

    $this->artisan('mosque:import', [
        '--max-pages' => 1,
        '--delay' => 0,
    ])->assertSuccessful();

    $mosque = Mosque::query()->where('dmi_code', 'M-1')->firstOrFail();

    expect($mosque->name)->toBe('Masjid Contoh')
        ->and($mosque->district_name)->toBe('Sawah Besar')
        ->and($mosque->city)->toBe('Jakarta Pusat')
        ->and($mosque->phone)->toBeNull()
        ->and($mosque->is_active)->toBeTrue()
        ->and($mosque->is_verified)->toBeFalse()
        ->and(Mosque::query()->count())->toBe(2);

    Http::assertSentCount(1);
});

it('infers a known district from the address when dmi omits it', function () {
    District::query()->create([
        'name' => 'Sawah Besar',
        'code' => '3173070',
    ]);

    Http::fake([
        'https://dmi.or.id/*' => Http::response(dmiImportHtml('Masjid Istiqlal', 'Jl. Taman Wijaya Kusuma Pasar Baru, Sawah Besar Jakarta Pusat', 1, 'Jakarta Pusat, DKI Jakarta'), 200),
    ]);

    $this->artisan('mosque:import', [
        '--max-pages' => 1,
        '--delay' => 0,
    ])->assertSuccessful();

    $mosque = Mosque::query()->where('dmi_code', 'M-1')->firstOrFail();

    expect($mosque->district_name)->toBe('Sawah Besar');
});

it('does not assign a district from another city', function () {
    District::query()->create([
        'name' => 'Menteng',
        'code' => '3173020',
    ]);

    Http::fake([
        'https://dmi.or.id/*' => Http::response(dmiImportHtml('Masjid Salah Kota', 'Jl. Menteng Raya, Jakarta Selatan', 1, 'Jakarta Selatan, DKI Jakarta'), 200),
    ]);

    $this->artisan('mosque:import', [
        '--max-pages' => 1,
        '--delay' => 0,
    ])->assertSuccessful();

    $mosque = Mosque::query()->where('dmi_code', 'M-1')->firstOrFail();

    expect($mosque->district_name)->toBeNull();
});

it('preserves admin active status and phone while resetting stale verification', function () {
    $existing = Mosque::query()->create([
        'dmi_code' => 'M-1',
        'name' => 'Nama Lama',
        'phone' => '021-999999',
        'source' => 'dmi',
        'is_verified' => true,
        'is_active' => false,
    ]);

    Http::fake([
        'https://dmi.or.id/*' => Http::response(dmiImportHtml('Nama Baru', 'Jl. Baru No. 1', 1), 200),
    ]);

    $this->artisan('mosque:import', [
        '--max-pages' => 1,
        '--delay' => 0,
    ])->assertSuccessful();

    $existing->refresh();

    expect($existing->name)->toBe('Nama Baru')
        ->and($existing->phone)->toBe('021-999999')
        ->and($existing->is_active)->toBeFalse()
        ->and($existing->is_verified)->toBeFalse();
});
