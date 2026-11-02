<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('institution_type', 20)->nullable()->after('education_level')->index();
        });

        // Batch reguler lama dianggap batch sekolah, kecuali yang berjenjang UMUM.
        // Batch VIP global dibiarkan NULL karena mencampur semua jenis institusi.
        DB::table('batches')
            ->where('name', 'not like', '%(GOR)%')
            ->update(['institution_type' => 'SEKOLAH']);

        DB::table('batches')
            ->where('name', 'not like', '%(GOR)%')
            ->where('education_level', 'UMUM')
            ->update(['institution_type' => 'UMUM']);
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['institution_type']);
            $table->dropColumn('institution_type');
        });
    }
};
