<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DistrictSeeder::class,
            SchoolSuggestionSeeder::class,
            UniversitySeeder::class,
        ]);

        $this->createAdmin();
    }

    /**
     * Buat akun admin dari env ADMIN_EMAIL/ADMIN_PASSWORD.
     *
     * Tidak ada kredensial default: deployment publik tidak boleh punya akun
     * dengan password yang bisa ditebak.
     */
    private function createAdmin(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD kosong — akun admin tidak dibuat.');

            return;
        }

        if (strlen((string) $password) < 12) {
            $this->command?->warn('ADMIN_PASSWORD minimal 12 karakter — akun admin tidak dibuat.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name'),
                'role' => 'admin',
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
