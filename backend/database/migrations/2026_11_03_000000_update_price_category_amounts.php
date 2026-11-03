<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('price_categories')->where('slug', 'reguler')->update([
            'amount' => 30_000,
            'updated_at' => $now,
        ]);

        DB::table('price_categories')->where('slug', 'vip')->update([
            'amount' => 50_000,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $now = now();

        DB::table('price_categories')->where('slug', 'reguler')->update([
            'amount' => 20_000,
            'updated_at' => $now,
        ]);

        DB::table('price_categories')->where('slug', 'vip')->update([
            'amount' => 40_000,
            'updated_at' => $now,
        ]);
    }
};
