<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $blueprint) {
            // Tambahkan kolom yang tadi error/null
            $blueprint->foreignId('district_id')->nullable()->constrained()->onDelete('set null');
            $blueprint->string('education_level')->nullable()->after('edition');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['district_id', 'education_level']);
        });
    }
};