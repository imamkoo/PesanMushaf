<?php

namespace App\Filament\Widgets;

use App\Models\District;
use App\Models\Registration;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopDistrictsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('10 Kecamatan dengan Pendaftar Terbanyak')
            ->description('Diurutkan dari total pendaftar. Kolom data lama disediakan agar entri lama yang belum lengkap tetap mudah diaudit.')
            ->query(
                District::query()
                    ->withCount('batches')
                    ->selectSub(
                        Registration::query()
                            ->whereColumn('registrations.district_id', 'districts.id')
                            ->whereNull('registrations.deleted_at')
                            ->selectRaw('COUNT(*)'),
                        'total_registrations'
                    )
                    ->selectSub(
                        Registration::query()
                            ->whereColumn('registrations.district_id', 'districts.id')
                            ->whereNull('registrations.deleted_at')
                            ->whereUmum()
                            ->selectRaw('COUNT(*)'),
                        'umum_registrations_count'
                    )
                    ->selectSub(
                        Registration::query()
                            ->whereColumn('registrations.district_id', 'districts.id')
                            ->whereNull('registrations.deleted_at')
                            ->whereNonUmum()
                            ->selectRaw('COUNT(*)'),
                        'non_umum_registrations_count'
                    )
                    ->selectSub(
                        Registration::query()
                            ->whereColumn('registrations.district_id', 'districts.id')
                            ->whereNull('registrations.deleted_at')
                            ->whereLegacyOrUnknownLevel()
                            ->selectRaw('COUNT(*)'),
                        'legacy_registrations_count'
                    )
                    ->orderByDesc('total_registrations')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Kecamatan')
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Kode')
                    ->placeholder('-'),
                TextColumn::make('batches_count')
                    ->label('Jumlah Batch')
                    ->numeric()
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_registrations')
                    ->label('Total Pendaftar')
                    ->numeric()
                    ->badge()
                    ->color('success'),
                TextColumn::make('umum_registrations_count')
                    ->label('UMUM')
                    ->numeric()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('non_umum_registrations_count')
                    ->label('Non-UMUM')
                    ->numeric()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('legacy_registrations_count')
                    ->label('Data Lama')
                    ->numeric()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false);
    }
}
