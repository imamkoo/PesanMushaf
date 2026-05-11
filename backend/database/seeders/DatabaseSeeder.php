<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
{
    // Memanggil DistrictSeeder
    $this->call([
        DistrictSeeder::class,
        SchoolSuggestionSeeder::class,
        UniversitySeeder::class,
    ]);
    
    // Anda juga bisa membuat akun admin default di sini untuk login Filament pertama kali
    \App\Models\User::factory()->create([
        'name' => 'Admin Jakarta',
        'email' => 'admin@jakarta.go.id',
        'role' => 'admin',
        'password' => bcrypt('password123'),
    ]);
}
}
