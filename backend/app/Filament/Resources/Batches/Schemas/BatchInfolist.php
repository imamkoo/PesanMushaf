<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class BatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama Batch / Label Box')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->columnSpanFull(),

                TextEntry::make('district.name')
                    ->label('📍 Wilayah Kecamatan')
                    ->icon('heroicon-m-map-pin')
                    ->weight(FontWeight::SemiBold)
                    ->default('VIP Global')
                    ->placeholder('VIP Global'),

                TextEntry::make('admin_segment')
                    ->label('🧭 Kategori')
                    ->badge()
                    ->state(fn ($record): string => match (true) {
                        $record->education_level === 'UMUM' => 'UMUM',
                        $record->education_level === null => 'VIP Global',
                        default => 'Non-UMUM',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'UMUM' => 'danger',
                        'Non-UMUM' => 'primary',
                        default => 'gray',
                    }),

                TextEntry::make('education_level')
                    ->label('🎓 Kategori Peserta')
                    ->badge()
                    ->default('VIP Global')
                    ->placeholder('VIP Global')
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'SD' => 'info',
                        'SMP' => 'warning',
                        'SMA' => 'success',
                        'UMUM' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : 'VIP Global'),

                TextEntry::make('batch_number')
                    ->label('Nomor Jilid (Batch)')
                    ->numeric(),

                TextEntry::make('max_capacity')
                    ->label('Kapasitas Maksimal')
                    ->suffix(' Halaman/Orang'),

                TextEntry::make('capacity_progress')
                    ->label('Progress Terisi')
                    ->badge()
                    ->color('info')
                    ->state(function ($record): string {
                        $registeredCount = $record->registrations()->count();

                        return $registeredCount.' / '.$record->max_capacity;
                    }),

                TextEntry::make('remaining_capacity')
                    ->label('Sisa Kapasitas')
                    ->badge()
                    ->color(function ($record): string {
                        $remaining = max($record->max_capacity - $record->registrations()->count(), 0);

                        return $remaining === 0 ? 'danger' : 'success';
                    })
                    ->state(function ($record): string {
                        $remaining = max($record->max_capacity - $record->registrations()->count(), 0);

                        return (string) $remaining;
                    }),

                TextEntry::make('is_full')
                    ->label('Status Kapasitas')
                    ->badge()
                    ->state(fn ($record): bool => $record->isFullByOccupancy())
                    ->formatStateUsing(fn (bool $state): string => $state ? 'SUDAH PENUH (Siap Cetak)' : 'MASIH TERBUKA')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
            ]);
    }
}
