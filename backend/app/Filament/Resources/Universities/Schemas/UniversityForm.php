<?php

namespace App\Filament\Resources\Universities\Schemas;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UniversityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kampus') // Sekarang Class Section sudah ter-import
                    ->description('Detail universitas di wilayah DKI Jakarta')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Universitas')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Kategori')
                            ->options([
                                'PTN' => 'Negeri (PTN)',
                                'PTS' => 'Swasta (PTS)',
                            ])
                            ->required(),
                        TextInput::make('city')
                            ->label('Kota/Wilayah')
                            ->default('Jakarta')
                            ->required(),
                    ])->columns(2),
            ]);
    }
}