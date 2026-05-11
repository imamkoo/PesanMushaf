<?php

namespace App\Filament\Resources\PriceCategories\Pages;

use App\Filament\Resources\PriceCategories\PriceCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPriceCategory extends EditRecord
{
    protected static string $resource = PriceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
