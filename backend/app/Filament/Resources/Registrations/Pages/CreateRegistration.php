<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\RegistrationService;

class CreateRegistration extends CreateRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Panggil Service Utama untuk memproses pendaftaran & penentuan buku
        return RegistrationService::register($data);
    }
}