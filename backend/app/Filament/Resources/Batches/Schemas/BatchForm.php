<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('district_id')
                    ->relationship('district', 'name')
                    ->label('Kecamatan')
                    ->helperText('Boleh kosong hanya untuk batch VIP global lintas kecamatan. Nama batch global harus memakai penanda (GOR).')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (blank($state)) {
                            $set('education_level', null);
                            $set('institution_type', null);
                        }
                    }),
                TextInput::make('name')
                    ->label('Nama Batch / Box')
                    ->required(),
                TextInput::make('batch_number')
                    ->label('Nomor Batch')
                    ->helperText('Gunakan prefix "V" untuk batch VIP global, mis. V1, V2.')
                    ->required(),
                Select::make('education_level')
                    ->label('Tingkat Pendidikan')
                    ->helperText('Wajib untuk batch kecamatan. Kosongkan untuk batch VIP global.')
                    ->options([
                        'SD' => 'SD',
                        'SMP' => 'SMP',
                        'SMA' => 'SMA',
                        'UMUM' => 'Umum / Mahasiswa',
                    ])
                    ->required(fn (Get $get): bool => filled($get('district_id'))),
                Select::make('institution_type')
                    ->label('Jenis Institusi')
                    ->helperText('Wajib untuk batch kecamatan. Menentukan batch yang dipakai pendaftar MASJID, KOMUNITAS, atau UMUM.')
                    ->options([
                        'SEKOLAH' => 'Sekolah',
                        'MASJID' => 'Masjid',
                        'KOMUNITAS' => 'Komunitas',
                        'UMUM' => 'Umum / Lembaga',
                        'KAMPUNG' => 'Mushaf Kampung (A3 warna)',
                    ])
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (filled($state) && $state !== 'SEKOLAH') {
                            $set('education_level', 'UMUM');
                        }
                    })
                    ->required(fn (Get $get): bool => filled($get('district_id'))),
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
