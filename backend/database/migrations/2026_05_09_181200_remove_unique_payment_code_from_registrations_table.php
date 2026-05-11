<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('registrations')->update([
            'total_payment' => DB::raw('base_price'),
        ]);

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('unique_payment_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('unique_payment_code')->default(0)->after('base_price');
        });
    }
};
