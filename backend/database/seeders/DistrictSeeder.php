<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\District;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // ID Provinsi DKI Jakarta di data BPS API EMSIFA
        $provinceId = '31'; 

        $this->command->info("Mengambil data Kota/Kabupaten di DKI Jakarta...");
        
        // TAMBAHKAN withoutVerifying() di sini
        $regenciesResponse = Http::withoutVerifying()->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json");
        
        if ($regenciesResponse->successful()) {
            $regencies = $regenciesResponse->json();

            foreach ($regencies as $regency) {
                $this->command->info("Mengambil Kecamatan untuk: " . $regency['name']);
                
                // TAMBAHKAN withoutVerifying() di sini juga
                $districtsResponse = Http::withoutVerifying()->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$regency['id']}.json");
                
                if ($districtsResponse->successful()) {
                    $districts = $districtsResponse->json();

                    foreach ($districts as $apiDistrict) {
                        District::firstOrCreate(
                            ['code' => $apiDistrict['id']], // Menggunakan ID BPS sebagai identifier unik (Smart Code)
                            [
                                'name'  => ucwords(strtolower($apiDistrict['name'])),
                                'slug'  => Str::slug($apiDistrict['name']),
                                'photo' => null,
                            ]
                        );
                    }
                }
            }
            $this->command->info("Semua Kecamatan DKI Jakarta berhasil di-seed!");
        } else {
            $this->command->error("Gagal terhubung ke API Wilayah Indonesia.");
        }
    }
}