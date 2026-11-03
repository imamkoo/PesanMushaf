<?php

namespace App\Filament\Resources\Mosques;

use App\Filament\Resources\Mosques\Pages\CreateMosque;
use App\Filament\Resources\Mosques\Pages\EditMosque;
use App\Filament\Resources\Mosques\Pages\ListMosques;
use App\Filament\Resources\Mosques\Schemas\MosqueForm;
use App\Filament\Resources\Mosques\Tables\MosquesTable;
use App\Models\Mosque;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MosqueResource extends Resource
{
    protected static ?string $model = Mosque::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Masjid';

    protected static ?string $modelLabel = 'Masjid';

    protected static ?string $pluralModelLabel = 'Masjid';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Masjid';

    public static function form(Schema $schema): Schema
    {
        return MosqueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MosquesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMosques::route('/'),
            'create' => CreateMosque::route('/create'),
            'edit' => EditMosque::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
