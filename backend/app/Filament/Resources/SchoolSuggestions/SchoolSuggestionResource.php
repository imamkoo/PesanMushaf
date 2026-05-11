<?php

namespace App\Filament\Resources\SchoolSuggestions;

use App\Filament\Resources\SchoolSuggestions\Pages\CreateSchoolSuggestion;
use App\Filament\Resources\SchoolSuggestions\Pages\EditSchoolSuggestion;
use App\Filament\Resources\SchoolSuggestions\Pages\ListSchoolSuggestions;
use App\Filament\Resources\SchoolSuggestions\Schemas\SchoolSuggestionForm;
use App\Filament\Resources\SchoolSuggestions\Tables\SchoolSuggestionsTable;
use App\Models\SchoolSuggestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SchoolSuggestionResource extends Resource
{
    protected static ?string $model = SchoolSuggestion::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'Schools';

    protected static ?string $pluralModelLabel = 'Schools';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Utama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    public static function form(Schema $schema): Schema
    {
        return SchoolSuggestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolSuggestionsTable::configure($table);
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
            'index' => ListSchoolSuggestions::route('/'),
            'create' => CreateSchoolSuggestion::route('/create'),
            'edit' => EditSchoolSuggestion::route('/{record}/edit'),
        ];
    }
}
