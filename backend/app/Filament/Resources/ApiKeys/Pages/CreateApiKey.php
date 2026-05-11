<?php

namespace App\Filament\Resources\ApiKeys\Pages;

use App\Filament\Resources\ApiKeys\ApiKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    // Optional: Redirect ke halaman list setelah buat key
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}