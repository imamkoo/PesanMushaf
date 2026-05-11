<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('school_name_normalized')->nullable()->after('school_name');
            $table->index('school_name_normalized', 'registrations_school_name_normalized_idx');
        });

        Schema::table('school_suggestions', function (Blueprint $table) {
            $table->string('school_name_normalized')->nullable()->after('name');
            $table->index('school_name_normalized', 'school_suggestions_school_name_normalized_idx');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('registrations_school_name_normalized_idx');
            $table->dropColumn('school_name_normalized');
        });

        Schema::table('school_suggestions', function (Blueprint $table) {
            $table->dropIndex('school_suggestions_school_name_normalized_idx');
            $table->dropColumn('school_name_normalized');
        });
    }
};
