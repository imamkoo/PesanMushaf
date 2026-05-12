<?php

namespace App\Filament\Resources\Batches\Tables;

use App\Models\Batch;
use App\Models\District;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withActiveRegistrationsCount())
            ->defaultSort('batch_number', 'asc')
            ->columns([
                TextColumn::make('batch_number')
                    ->label('Jilid')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Nama Batch')
                    ->description(fn (Batch $record): string => $record->slug)
                    ->searchable(),
                TextColumn::make('admin_segment')
                    ->label('Kategori')
                    ->badge()
                    ->state(fn (Batch $record): string => self::adminSegmentLabel($record))
                    ->color(fn (Batch $record): string => match (self::adminSegmentLabel($record)) {
                        'UMUM' => 'danger',
                        'Non-UMUM' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->placeholder('VIP Global')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('education_level')
                    ->label('Jenjang')
                    ->badge()
                    ->placeholder('VIP Global')
                    ->color(fn (?string $state): string => match ($state) {
                        'SD' => 'success',
                        'SMP' => 'warning',
                        'SMA' => 'danger',
                        'UMUM' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : 'VIP Global'),
                TextColumn::make('registrations_count')
                    ->label('Terisi / Kapasitas')
                    ->state(fn (Batch $record): string => ($record->registrations_count ?? 0).' / '.$record->max_capacity)
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('registrations_count', $direction);
                    }),
                TextColumn::make('fill_percentage')
                    ->label('Progress')
                    ->state(fn (Batch $record): string => $record->fillPercentage((int) ($record->registrations_count ?? 0)).'%')
                    ->badge()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByOccupancy($direction))
                    ->color(function (Batch $record): string {
                        $pct = $record->occupancyRatio((int) ($record->registrations_count ?? 0));

                        return match (true) {
                            $pct >= 1.0 => 'danger',
                            $pct >= 0.8 => 'warning',
                            default => 'success',
                        };
                    }),
                IconColumn::make('is_full')
                    ->label('Penuh')
                    ->state(fn (Batch $record): bool => $record->isFullByOccupancy((int) ($record->registrations_count ?? 0)))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('max_capacity')
                    ->label('Kapasitas')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                TernaryFilter::make('is_full')
                    ->label('Status Kuota')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah penuh')
                    ->falseLabel('Masih terbuka')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereFullByOccupancy(true),
                        false: fn (Builder $query): Builder => $query->whereFullByOccupancy(false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('progress_band')
                    ->label('Progress Batch')
                    ->options([
                        'full' => '100% (Penuh)',
                        'high' => '80 - 99%',
                        'low' => 'Di bawah 80%',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'full' => $query->whereFullByOccupancy(true),
                            'high' => $query->whereOccupancyBetween(0.8, 1.0),
                            'low' => $query->whereOccupancyBetween(null, 0.8),
                            default => $query,
                        };
                    }),
                SelectFilter::make('admin_segment')
                    ->label('Kategori')
                    ->options([
                        'umum' => 'UMUM',
                        'non_umum' => 'Non-UMUM',
                        'global_null' => 'VIP Global',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'umum' => $query->whereUmum(),
                            'non_umum' => $query->whereNonUmum(),
                            'global_null' => $query->whereGlobalOrNull(),
                            default => $query,
                        };
                    }),
                Filter::make('vip_global')
                    ->label('VIP Jakarta (global)')
                    ->query(fn (Builder $query): Builder => $query->whereNull('district_id')->whereNull('education_level'))
                    ->toggle(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    private static function adminSegmentLabel(Batch $record): string
    {
        return match (true) {
            $record->education_level === 'UMUM' => 'UMUM',
            $record->education_level === null => 'VIP Global',
            default => 'Non-UMUM',
        };
    }
}
