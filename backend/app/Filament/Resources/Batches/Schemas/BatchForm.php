<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('district_id')
                    ->relationship('district', 'name')
                    ->label('Kecamatan')
                    ->helperText('Boleh kosong untuk batch VIP global lintas kecamatan.')
                    ->searchable(),
                TextInput::make('name')
                    ->label('Nama Batch / Box')
                    ->required(),
                TextInput::make('batch_number')
                    ->label('Nomor Batch')
                    ->helperText('Gunakan prefix "V" untuk batch VIP global, mis. V1, V2.')
                    ->required(),
                Select::make('education_level')
                    ->label('Tingkat Pendidikan')
                    ->helperText('Kosongkan untuk batch VIP global (mencampur semua jenjang).')
                    ->options([
                        'SD' => 'SD',
                        'SMP' => 'SMP',
                        'SMA' => 'SMA',
                        'UMUM' => 'Umum / Mahasiswa',
                    ]),
                TextInput::make('max_capacity')
                    ->numeric()
                    ->default(603)
                    ->disabled(),
                Toggle::make('is_full')
                    ->label('Sudah Penuh?')
                    ->disabled(),
            ]);
    }
}