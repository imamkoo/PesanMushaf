<?php

use App\Support\IndonesianPhone;

it('normalizes Indonesian numbers to 62 prefix', function (string $input, string $expected) {
    expect(IndonesianPhone::normalizeWhatsAppTarget($input))->toBe($expected);
})->with([
    ['6281234567890', '6281234567890'],
    ['081234567890', '6281234567890'],
    ['81234567890', '6281234567890'],
    ['+62 812-3456-7890', '6281234567890'],
    ['  08 12 345 678 90  ', '6281234567890'],
]);

it('returns empty for non digit input', function () {
    expect(IndonesianPhone::normalizeWhatsAppTarget('abc'))->toBe('');
});
