<?php

use App\Models\Registration;
use App\Support\IndonesianPhone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->timestamp('whatsapp_payment_notified_at')->nullable()->after('payment_status');
        });

        Registration::query()->chunkById(200, function ($registrations): void {
            foreach ($registrations as $registration) {
                $normalized = IndonesianPhone::normalizeWhatsAppTarget((string) $registration->phone_number);
                if ($normalized !== '' && $normalized !== $registration->phone_number) {
                    $registration->forceFill(['phone_number' => $normalized])->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('whatsapp_payment_notified_at');
        });
    }
};
