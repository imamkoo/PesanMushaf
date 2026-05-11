<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
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
                    ->default('VIP Jakarta (Global)')
                    ->placeholder('VIP Jakarta (Global)'),

                TextEntry::make('education_level')
                    ->label('🎓 Kategori Peserta')
                    ->badge()
                    ->default('Semua jenjang')
                    ->placeholder('Semua jenjang')
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'SD' => 'info',
                        'SMP' => 'warning',
                        'SMA' => 'success',
                        'UMUM' => 'danger',
                        default => 'gray',
                    }),

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
                    ->formatStateUsing(fn ($state): string => (bool) $state ? 'SUDAH PENUH (Siap Cetak)' : 'MASIH TERBUKA')
                    ->color(fn ($state): string => (bool) $state ? 'danger' : 'success')
                    ->icon(fn ($state): string => (bool) $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
            ]);
    }
}