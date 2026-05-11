<?php

namespace App\Filament\Resources\ApiKeys\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section; 
use Filament\Forms\Components\TextInput; 
use Illuminate\Support\Str;

class ApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('API Key Management')
                ->description('Gunakan key ini untuk mengizinkan aplikasi luar mengakses API pendaftaran.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Aplikasi / Client')
                        ->required(),

                    TextInput::make('key')
                        ->label('API Key Secret')
                        ->default(fn () => 'mushaf_' . Str::random(32))
                        ->readonly()
                        ->required(),
                ])
        ]);
    }
}