<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('institution_type', 20)->default('SEKOLAH')->after('education_level')->index();
        });

        // Baris lama berjenjang UMUM memang bukan institusi sekolah.
        DB::table('registrations')
            ->where('education_level', 'UMUM')
            ->update(['institution_type' => 'UMUM']);
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['institution_type']);
            $table->dropColumn('institution_type');
        });
    }
};
