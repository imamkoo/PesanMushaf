<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            // Drop existing FK with cascadeOnDelete then re-add as nullable + set null
            $table->dropForeign(['district_id']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->change();
            $table->string('education_level')->nullable()->change();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreign('district_id')
                ->references('id')->on('districts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable(false)->change();
            $table->string('education_level')->nullable(false)->change();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreign('district_id')
                ->references('id')->on('districts')
                ->cascadeOnDelete();
        });
    }
};
