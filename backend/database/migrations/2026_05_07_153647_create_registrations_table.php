<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('registrations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('batch_id')->constrained()->cascadeOnDelete(); // Relasi ke Gelombang/Buku
        
        // Identitas Pendaftar (Sesuai Referensi Gambar)
        $table->string('name');
        $table->string('phone_number');
        $table->string('email')->nullable();
        
        // Logistik
        $table->string('edition')->default('reguler'); //VIP dan Reguler
        $table->string('school_name')->nullable();
        $table->string('registration_code')->unique(); // Booking trx id (Smart Code)
        $table->integer('page_number'); 
        
        // Finansial (Sesuai Referensi Gambar)
        $table->integer('base_price')->default(10000); 
        $table->integer('unique_payment_code'); 
        $table->integer('total_payment'); // Total amount
        $table->enum('payment_status', ['pending', 'success', 'failed'])->default('pending'); // Is paid
        $table->string('payment_receipt')->nullable(); // Foto bukti transfer
        
        $table->softDeletes();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};