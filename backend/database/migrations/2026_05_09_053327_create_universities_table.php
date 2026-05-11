<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('universities', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('type')->nullable(); // PTN atau PTS
        $table->string('city')->default('Jakarta');
        $table->timestamps();
    });

    // Sekalian tambahkan kolom university_id ke tabel registrations
    Schema::table('registrations', function (Blueprint $table) {
        $table->foreignId('university_id')->nullable()->constrained()->onDelete('set null');
    });
}
};
