<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RegistrationService;
use App\Models\Batch;

class TestRegistration extends Command
{
    // Ini nama perintah yang akan kita panggil di terminal
    protected $signature = 'test:mass-reg';

    protected $description = 'Simulasi pendaftaran massal Event HUT Jakarta';

    public function handle(RegistrationService $service)
    {
        $this->info('Mencari data Batch yang tersedia...');
        
        // Cari batch pertama yang belum penuh
        $batch = Batch::where('is_full', false)->first();

        if (!$batch) {
            $this->error('Gagal: Anda belum membuat Batch di Dashboard, atau semua Batch sudah penuh!');
            return;
        }

        $this->info("Menemukan Batch: {$batch->name}. Memulai simulasi pendaftaran...");

        // Simulasi data 3 warga Jakarta
        $dummyData = [
            [
                'name' => 'Ahmad Sudirman',
                'email' => 'ahmad@contoh.com',
                'district_id' => $batch->district_id,
                'school_name' => 'Umum',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti@contoh.com',
                'district_id' => $batch->district_id,
                'school_name' => 'SMA 1 Jakarta',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@contoh.com',
                'district_id' => $batch->district_id,
                'school_name' => 'Kecamatan Gambir',
            ],
        ];

        // Jalankan mesin Service kita
        $result = $service->processMassRegistration($dummyData);

        // Tampilkan hasil di terminal
        if ($result['status'] === 'success') {
            $this->info('SUKSES: ' . $result['message']);
        } else {
            $this->error('GAGAL: ' . $result['message']);
        }
    }
}