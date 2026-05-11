<?php

namespace App\Filament\Resources\PriceCategories;

use App\Filament\Resources\PriceCategories\Pages\CreatePriceCategory;
use App\Filament\Resources\PriceCategories\Pages\EditPriceCategory;
use App\Filament\Resources\PriceCategories\Pages\ListPriceCategories;
use App\Filament\Resources\PriceCategories\Schemas\PriceCategoryForm;
use App\Filament\Resources\PriceCategories\Tables\PriceCategoriesTable;
use App\Models\PriceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PriceCategoryResource extends Resource
{
    protected static ?string $model = PriceCategory::class;

    protected static ?string $navigationLabel = 'Harga & kategori';

    protected static ?string $modelLabel = 'kategori harga';

    protected static ?string $pluralModelLabel = 'Kategori harga';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PriceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceCategories::route('/'),
            'create' => CreatePriceCategory::route('/create'),
            'edit' => EditPriceCategory::route('/{record}/edit'),
        ];
    }
}
