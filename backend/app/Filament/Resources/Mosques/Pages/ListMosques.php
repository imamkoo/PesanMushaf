<?php

namespace App\Filament\Resources\Mosques\Pages;

use App\Filament\Resources\Mosques\MosqueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMosques extends ListRecords
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
