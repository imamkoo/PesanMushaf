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
                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->placeholder('VIP Jakarta (Global)')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('education_level')
                    ->label('Jenjang')
                    ->badge()
                    ->placeholder('Semua jenjang')
                    ->color(fn (?string $state): string => match ($state) {
                        'SD' => 'success',
                        'SMP' => 'warning',
                        'SMA' => 'danger',
                        'UMUM' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('registrations_count')
                    ->label('Terisi / Kapasitas')
                    ->state(fn (Batch $record): string => ($record->registrations_count ?? 0).' / '.$record->max_capacity)
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('registrations_count', $direction);
                    }),
                TextColumn::make('fill_percentage')
                    ->label('Progress')
                    ->state(function (Batch $record): string {
                        if ($record->max_capacity <= 0) {
                            return '0%';
                        }

                        $pct = min(100, round((($record->registrations_count ?? 0) / $record->max_capacity) * 100));

                        return $pct.'%';
                    })
                    ->badge()
                    ->color(function (Batch $record): string {
                        if ($record->max_capacity <= 0) {
                            return 'gray';
                        }

                        $pct = ($record->registrations_count ?? 0) / $record->max_capacity;

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
}
