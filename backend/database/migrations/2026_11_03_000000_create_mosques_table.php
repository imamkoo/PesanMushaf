<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();
            $table->string('dmi_code', 20)->nullable()->unique();
            $table->json('external_ids')->nullable();
            $table->string('name', 180);
            $table->string('name_normalized', 180)->nullable()->index();
            $table->string('address', 255)->nullable();
            $table->string('village', 120)->nullable();
            $table->string('district_name', 120)->nullable()->index();
            $table->string('city', 120)->nullable()->index();
            $table->string('province', 120)->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('source', 20)->default('manual');
            $table->string('source_url', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['province', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosques');
    }
};
