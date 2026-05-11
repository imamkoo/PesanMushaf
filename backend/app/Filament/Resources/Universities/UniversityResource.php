<?php

namespace App\Filament\Resources\Universities;

use App\Filament\Resources\Universities\Pages\CreateUniversity;
use App\Filament\Resources\Universities\Pages\EditUniversity;
use App\Filament\Resources\Universities\Pages\ListUniversities;
use App\Filament\Resources\Universities\Schemas\UniversityForm;
use App\Filament\Resources\Universities\Tables\UniversitiesTable;
use App\Models\University;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UniversityResource extends Resource
{
    protected static ?string $model = University::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Utama';

    public static function form(Schema $schema): Schema
    {
        return UniversityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UniversitiesTable::configure($table);
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
            'index' => ListUniversities::route('/'),
            'create' => CreateUniversity::route('/create'),
            'edit' => EditUniversity::route('/{record}/edit'),
        ];
    }
}
