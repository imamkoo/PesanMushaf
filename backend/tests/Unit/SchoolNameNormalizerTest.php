<?php

use App\Support\SchoolNameNormalizer;

it('normalizes Indonesian school name variations to a single canonical form', function (string $raw, string $expected) {
    expect(SchoolNameNormalizer::normalize($raw))->toBe($expected);
})->with([
    ['SMP Negeri 1 Cilandak', 'smpn 1 cilandak'],
    ['SMPN 1 Cilandak', 'smpn 1 cilandak'],
    ['SMP N 1 Cilandak', 'smpn 1 cilandak'],
    ['  SMP Negeri  1  Cilandak ', 'smpn 1 cilandak'],
    ['SMP Negeri 1, Cilandak.', 'smpn 1 cilandak'],
    ['Sekolah Menengah Pertama Negeri 1 Cilandak', 'smpn 1 cilandak'],
    ['SMA NEGERI 6 Jakarta', 'sman 6 jakarta'],
    ['SD Negeri 03 Pagi', 'sdn 03 pagi'],
    ['MTs N 1 Jakarta', 'mtsn 1 jakarta'],
    ['Madrasah Aliyah Negeri 4', 'man 4'],
    ['Pondok Pesantren Al-Hikmah', 'pondok al hikmah'],
]);

it('returns null for empty or whitespace-only input', function (?string $raw) {
    expect(SchoolNameNormalizer::normalize($raw))->toBeNull();
})->with([
    null,
    '',
    '   ',
    "\n\t",
]);

it('produces a 1.0 similarity for identical normalized forms', function () {
    expect(SchoolNameNormalizer::similarity('SMPN 1 Cilandak', 'SMP N 1 Cilandak'))->toBe(1.0);
});

it('returns similarity above the fuzzy threshold for typo variants', function () {
    $score = SchoolNameNormalizer::similarity('SMPN1 CILANDA', 'SMPN 1 Cilandak');

    expect($score)->toBeGreaterThan(0.85);
});

it('returns low similarity for unrelated names', function () {
    $score = SchoolNameNormalizer::similarity('SMPN 1 Cilandak', 'Pondok Al-Hikmah');

    expect($score)->toBeLessThan(0.5);
});
