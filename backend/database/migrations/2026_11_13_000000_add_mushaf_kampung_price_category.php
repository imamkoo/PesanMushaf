<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Aditif & idempoten: hanya menambah paket Mushaf Kampung bila belum ada.
        $exists = DB::table('price_categories')->where('slug', 'kampung')->exists();

        if ($exists) {
            return;
        }

        DB::table('price_categories')->insert([
            'slug' => 'kampung',
            'name' => 'Mushaf Kampung',
            'amount' => 20_000,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Nonaktifkan saja, jangan hapus: baris mungkin sudah dirujuk pendaftaran.
        DB::table('price_categories')->where('slug', 'kampung')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }
};
