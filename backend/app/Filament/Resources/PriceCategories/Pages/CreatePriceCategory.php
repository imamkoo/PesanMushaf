<?php

namespace App\Filament\Resources\PriceCategories\Pages;

use App\Filament\Resources\PriceCategories\PriceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceCategory extends CreateRecord
{
    protected static string $resource = PriceCategoryResource::class;
}
