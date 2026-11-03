<?php

use App\Support\DmiMosqueParser;

it('parses dmi mosque rows including coordinates and region parts', function () {
    $html = <<<'HTML'
<table class="masjid-dmi-table">
    <tbody>
        <tr>
            <td>M-1</td>
            <td><strong>Hidayatul Musyawarah</strong></td>
            <td>JL. Kayu Manis AMD 28 Condet
                <a class="masjid-dmi-map" href="https://www.google.com/maps/search/?api=1&amp;query=-6.2906100,106.8510000">(lokasi)</a>
            </td>
            <td>Bale Kambang, Kramat jati, Jakarta Timur, DKI Jakarta</td>
            <td>021 8013940</td>
        </tr>
        <tr>
            <td>M-2</td>
            <td>Masjid Tanpa Koordinat</td>
            <td>Jl. Contoh No. 2</td>
            <td>Jakarta Selatan, DKI Jakarta</td>
            <td></td>
        </tr>
    </tbody>
</table>
HTML;

    $rows = DmiMosqueParser::parse($html);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['dmi_code'])->toBe('M-1')
        ->and($rows[0]['name'])->toBe('Hidayatul Musyawarah')
        ->and($rows[0]['address'])->toBe('JL. Kayu Manis AMD 28 Condet')
        ->and($rows[0]['village'])->toBe('Bale Kambang')
        ->and($rows[0]['district_name'])->toBe('Kramat jati')
        ->and($rows[0]['city'])->toBe('Jakarta Timur')
        ->and($rows[0]['province'])->toBe('DKI Jakarta')
        ->and($rows[0]['latitude'])->toBe(-6.29061)
        ->and($rows[0]['longitude'])->toBe(106.851)
        ->and($rows[0]['phone'])->toBe('021 8013940')
        ->and($rows[1]['district_name'])->toBeNull()
        ->and($rows[1]['city'])->toBe('Jakarta Selatan')
        ->and($rows[1]['province'])->toBe('DKI Jakarta')
        ->and($rows[1]['latitude'])->toBeNull()
        ->and($rows[1]['phone'])->toBeNull();
});

it('returns an empty list when the dmi table is not present', function () {
    expect(DmiMosqueParser::parse('<html><body>Tidak ada tabel</body></html>'))->toBe([]);
});
