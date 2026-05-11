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
            ->description('Diurutkan dari pendaftaran terbanyak (semua status pembayaran).')
            ->query(
                District::query()
                    ->withCount('batches')
                    ->selectSub(
                        Registration::query()
                            ->whereColumn('registrations.district_id', 'districts.id')
                            ->selectRaw('COUNT(*)'),
                        'registrations_count'
                    )
                    ->orderByDesc('registrations_count')
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
                TextColumn::make('registrations_count')
                    ->label('Total Pendaftar')
                    ->numeric()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
