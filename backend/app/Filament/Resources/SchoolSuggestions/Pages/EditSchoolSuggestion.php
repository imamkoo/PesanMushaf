<?php

namespace App\Filament\Resources\SchoolSuggestions\Pages;

use App\Filament\Resources\SchoolSuggestions\SchoolSuggestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolSuggestion extends EditRecord
{
    protected static string $resource = SchoolSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
