<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use App\Models\Registration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $recordTitleAttribute = 'registration_code';

    public function getTabs(): array
    {
        $baseQuery = Registration::query()->where('batch_id', $this->getOwnerRecord()->getKey());

        return [
            'all' => Tab::make('Semua')
                ->badge((clone $baseQuery)->count())
                ->icon('heroicon-m-queue-list'),
            'umum' => Tab::make('UMUM')
                ->badge((clone $baseQuery)->whereUmum()->count())
                ->badgeColor('danger')
                ->icon('heroicon-m-identification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereUmum()),
            'non_umum' => Tab::make('Non-UMUM')
                ->badge((clone $baseQuery)->whereNonUmum()->count())
                ->badgeColor('primary')
                ->icon('heroicon-m-academic-cap')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNonUmum()),
            'legacy' => Tab::make('Data Lama')
                ->badge((clone $baseQuery)->whereLegacyOrUnknownLevel()->count())
                ->badgeColor('gray')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereLegacyOrUnknownLevel()),
        ];
    }

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
                Tables\Columns\TextColumn::make('education_level')
                    ->label('Jenjang')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : 'Data Lama')
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'SD' => 'info',
                        'SMP' => 'warning',
                        'SMA' => 'success',
                        'UMUM' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('school_name')
                    ->label('Sekolah / Kampus / Instansi')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->state(fn (Registration $record): string => filled($record->nik)
                        ? (string) $record->nik
                        : ($record->education_level === 'UMUM' ? 'Belum diisi (cek data lama)' : 'Tidak wajib'))
                    ->copyable(fn (Registration $record): bool => filled($record->nik))
                    ->color(fn (Registration $record): string => $record->education_level === 'UMUM' && blank($record->nik) ? 'warning' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->state(fn (Registration $record): string => filled($record->address)
                        ? (string) $record->address
                        : ($record->education_level === 'UMUM' ? 'Belum diisi (cek data lama)' : 'Tidak wajib'))
                    ->limit(48)
                    ->wrap()
                    ->color(fn (Registration $record): string => $record->education_level === 'UMUM' && blank($record->address) ? 'warning' : 'gray')
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
