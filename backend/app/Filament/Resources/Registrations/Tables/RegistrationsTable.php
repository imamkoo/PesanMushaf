<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\District;
use App\Models\PriceCategory;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('education_level')
                    ->label('Jenjang')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'SD' => 'info',
                        'SMP' => 'warning',
                        'SMA' => 'success',
                        'UMUM' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('edition')
                    ->label('Edisi')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => $state === 'vip' ? 'warning' : 'info')
                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),

                TextColumn::make('school_name')
                    ->label('Sekolah / Instansi')
                    ->searchable()
                    ->limit(32)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->searchable()
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('registration_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('batch.name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('page_number')
                    ->label('Halaman')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('total_payment')
                    ->label('Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Lunas',
                        'failed' => 'Batal',
                    ]),
                SelectFilter::make('edition')
                    ->label('Edisi')
                    ->options(fn (): array => PriceCategory::query()
                        ->orderBy('sort_order')
                        ->pluck('name', 'slug')
                        ->toArray()),
                SelectFilter::make('education_level')
                    ->label('Jenjang')
                    ->options([
                        'SD' => 'SD',
                        'SMP' => 'SMP',
                        'SMA' => 'SMA',
                        'UMUM' => 'UMUM',
                    ]),
                SelectFilter::make('district_id')
                    ->label('Kecamatan')
                    ->options(fn (): array => District::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_payment_success')
                        ->label('Tandai Lunas')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai pembayaran sebagai LUNAS?')
                        ->modalDescription('Semua pendaftaran terpilih akan diubah ke status success. Aksi ini akan dicatat pada updated_at.')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->payment_status = 'success';
                                $record->save();
                                $count++;
                            }

                            Notification::make()
                                ->title($count.' pendaftaran ditandai LUNAS')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_payment_failed')
                        ->label('Tandai Batal')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai pembayaran sebagai BATAL?')
                        ->modalDescription('Semua pendaftaran terpilih akan diubah ke status failed.')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->payment_status = 'failed';
                                $record->save();
                                $count++;
                            }

                            Notification::make()
                                ->title($count.' pendaftaran ditandai BATAL')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
