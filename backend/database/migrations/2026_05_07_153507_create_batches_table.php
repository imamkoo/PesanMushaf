<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name'); 
            $table->string('slug')->unique();
            $table->string('batch_number'); // Contoh: GEL-01
            $table->string('education_level'); // SD, SMP, SMA, Umum
            $table->integer('max_capacity')->default(603);
            $table->boolean('is_full')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};