<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class UniversitySeeder extends Seeder
{
    public function run(): void
    {
        // Link API Aktif (REST Service - Global University Database)
        $url = 'http://universities.hipolabs.com/search?country=Indonesia';

        $this->command->info("🛰️ Menghubungi API Hipolabs untuk data universitas Indonesia...");

        try {
            // Mengambil data dengan bypass SSL untuk stabilitas di macOS/Localhost
            $response = Http::withoutVerifying()->get($url);

            if ($response->successful()) {
                $universities = $response->json();
                $count = 0;

                // Daftar filter nama untuk memastikan kita fokus ke area Jakarta & Sekitarnya
                // tanpa harus hardcode daftar namanya satu per satu.
                $jakartaKeywords = ['JAKARTA', 'BINUS', 'TRISAKTI', 'ATMA JAYA', 'TARUMANAGARA', 'MERCU BUANA', 'PERSETIYA', 'GUNADARMA', 'INDONESIA'];

                foreach ($universities as $uni) {
                    $name = strtoupper($uni['name']);
                    
                    // Logika Filter: Jika nama mengandung keyword Jakarta atau kampus besar Jakarta
                    $isJakarta = false;
                    foreach ($jakartaKeywords as $key) {
                        if (str_contains($name, $key)) {
                            $isJakarta = true;
                            break;
                        }
                    }

                    if ($isJakarta) {
                        University::updateOrCreate(
                            ['name' => $uni['name']], 
                            [
                                'type' => str_contains($name, 'NEGERI') ? 'PTN' : 'PTS',
                                'city' => 'Jakarta'
                            ]
                        );
                        $count++;
                    }
                }

                $this->command->info("✅ Berhasil sinkronisasi {$count} Universitas Jakarta dari API!");
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Koneksi API Gagal: " . $e->getMessage());
        }
    }
}