<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\District;
use App\Models\PriceCategory;
use App\Models\Registration;
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
use Illuminate\Support\Str;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('registration_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn (Registration $record): string => 'Daftar '.($record->created_at?->format('d M Y H:i') ?? '-')),
                    
                TextColumn::make('district.name')
                        ->label('Kecamatan')
                        ->sortable()
                        ->searchable()
                        ->placeholder('-'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Registration $record): ?string => self::schoolSummary($record))
                    ->wrap(),


                TextColumn::make('batch.name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->limit(32)
                    ->wrap(),

                TextColumn::make('page_number')
                    ->label('Halaman')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('edition')
                    ->label('Kategori')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => $state === 'vip' ? 'warning' : 'info')
                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),

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
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : 'Data Lama'),

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
                
                TextColumn::make('admin_segment')
                    ->label('Kelompok')
                    ->badge()
                    ->state(fn (Registration $record): string => self::adminSegmentLabel($record->education_level))
                    ->color(fn (Registration $record): string => match (self::adminSegmentLabel($record->education_level)) {
                        'UMUM' => 'danger',
                        'Non-UMUM' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('school_name')
                    ->label('Sekolah / Kampus / Instansi')
                    ->state(function (Registration $record): string {
                        if (filled($record->school_name)) {
                            return (string) $record->school_name;
                        }

                        return $record->education_level === 'UMUM'
                            ? 'Instansi / kampus belum diisi'
                            : '-';
                    })
                    ->description(fn (Registration $record): ?string => $record->education_level === 'UMUM' ? 'Identitas instansi peserta UMUM' : null)
                    ->searchable()
                    ->limit(48)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->state(fn (Registration $record): string => self::nikState($record))
                    ->searchable()
                    ->sortable()
                    ->copyable(fn (Registration $record): bool => filled($record->nik))
                    ->color(fn (Registration $record): string => $record->education_level === 'UMUM' && blank($record->nik) ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->state(fn (Registration $record): string => self::addressState($record))
                    ->searchable()
                    ->limit(48)
                    ->wrap()
                    ->color(fn (Registration $record): string => $record->education_level === 'UMUM' && blank($record->address) ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_payment')
                    ->label('Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->label('Kategori')
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

    private static function adminSegmentLabel(?string $educationLevel): string
    {
        return match (true) {
            $educationLevel === 'UMUM' => 'UMUM',
            blank($educationLevel) => 'Data Lama',
            default => 'Non-UMUM',
        };
    }

    private static function schoolSummary(Registration $record): ?string
    {
        if (blank($record->school_name)) {
            return null;
        }

        return Str::limit((string) $record->school_name, 56);
    }

    private static function nikState(Registration $record): string
    {
        if (filled($record->nik)) {
            return (string) $record->nik;
        }

        return $record->education_level === 'UMUM'
            ? 'Belum diisi (cek data lama)'
            : 'Tidak wajib';
    }

    private static function addressState(Registration $record): string
    {
        if (filled($record->address)) {
            return (string) $record->address;
        }

        return $record->education_level === 'UMUM'
            ? 'Belum diisi (cek data lama)'
            : 'Tidak wajib';
    }
}
