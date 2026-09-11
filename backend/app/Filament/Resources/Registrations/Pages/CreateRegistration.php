<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Services\RegistrationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRegistration extends CreateRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Panggil Service Utama untuk memproses pendaftaran & penentuan buku
        return RegistrationService::register($data);
    }
}
