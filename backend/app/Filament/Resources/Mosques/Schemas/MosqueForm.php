<?php

namespace App\Filament\Resources\Mosques\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MosqueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Masjid')
                    ->required()
                    ->maxLength(180),
                TextInput::make('dmi_code')
                    ->label('Kode DMI')
                    ->helperText('Contoh: M-1417. Biarkan kosong untuk masjid yang dimasukkan manual.')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(2)
                    ->maxLength(255),
                TextInput::make('village')
                    ->label('Kelurahan')
                    ->maxLength(120),
                TextInput::make('district_name')
                    ->label('Kecamatan')
                    ->maxLength(120),
                TextInput::make('city')
                    ->label('Kota / Kabupaten')
                    ->maxLength(120),
                TextInput::make('province')
                    ->label('Provinsi')
                    ->maxLength(120),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->rules(['nullable', 'numeric', 'between:-90,90']),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->rules(['nullable', 'numeric', 'between:-180,180']),
                TextInput::make('phone')
                    ->label('Telepon (internal)')
                    ->helperText('Tidak pernah ditampilkan ke frontend publik.')
                    ->maxLength(40),
                Select::make('source')
                    ->label('Sumber Data')
                    ->options([
                        'manual' => 'Manual admin',
                        'dmi' => 'DMI',
                        'participant' => 'Usulan peserta',
                    ])
                    ->default('manual')
                    ->required(),
                Toggle::make('is_verified')
                    ->label('Sudah Diverifikasi')
                    ->default(false),
                Toggle::make('is_active')
                    ->label('Aktif di Katalog')
                    ->default(true),
            ]);
    }
}
