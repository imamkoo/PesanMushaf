<?php

namespace App\Filament\Resources\SchoolSuggestions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolSuggestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('district_id')
                    ->label('Kecamatan')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('education_level')
                    ->label('Jenjang')
                    ->options([
                        'SD' => 'SD / sederajat',
                        'SMP' => 'SMP / sederajat',
                        'SMA' => 'SMA / sederajat',
                    ])
                    ->required(),

                TextInput::make('name')
                    ->label('Nama sekolah (tampil di dropdown publik)')
                    ->helperText('Data awal diisi lewat SchoolSuggestionSeeder dari API direktori sekolah nasional; entri di sini menambah atau mengoreksi master lokal.')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
