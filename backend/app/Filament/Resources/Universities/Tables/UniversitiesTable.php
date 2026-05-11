<?php

namespace App\Filament\Resources\Universities\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class UniversitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kampus')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'PTN' ? 'success' : 'warning'),

                // Menghitung otomatis pendaftar dari kampus ini (Logistik Friendly)
                Tables\Columns\TextColumn::make('registrations_count')
                    ->label('Total Pendaftar')
                    ->counts('registrations')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PTN' => 'Negeri',
                        'PTS' => 'Swasta',
                    ]),
            ]);
    }
}