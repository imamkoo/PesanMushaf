<?php

namespace App\Filament\Resources\SchoolSuggestions\Pages;

use App\Filament\Resources\SchoolSuggestions\SchoolSuggestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolSuggestions extends ListRecords
{
    protected static string $resource = SchoolSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
