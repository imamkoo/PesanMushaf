<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $recordTitleAttribute = 'registration_code';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('registration_code')
            ->columns([
                Tables\Columns\TextColumn::make('registration_code')
                    ->label('Kode Booking')
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('school_name')
                    ->label('Sekolah / Instansi')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('page_number')
                    ->label('Halaman')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Lunas',
                        'failed' => 'Batal',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
