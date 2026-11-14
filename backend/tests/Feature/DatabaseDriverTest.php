<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CI menjalankan suite yang sama di SQLite (default) dan MySQL (job
 * "Backend Tests (MySQL portability)") untuk membuktikan migrasi tetap portabel
 * saat backend dipindah ke cPanel. EXPECT_DB_DRIVER memastikan job MySQL
 * benar-benar memakai MySQL, bukan diam-diam jatuh ke SQLite.
 */
test('database driver matches the driver expected for this run', function () {
    $expected = env('EXPECT_DB_DRIVER');

    if ($expected === null) {
        expect(DB::connection()->getDriverName())->toBeIn(['sqlite', 'mysql', 'pgsql']);

        return;
    }

    expect(DB::connection()->getDriverName())->toBe($expected);
});

test('schema hasil migrasi tersedia di driver yang sedang diuji', function () {
    expect(Schema::hasTable('registrations'))->toBeTrue()
        ->and(Schema::hasTable('batches'))->toBeTrue()
        ->and(Schema::hasTable('price_categories'))->toBeTrue();
});
