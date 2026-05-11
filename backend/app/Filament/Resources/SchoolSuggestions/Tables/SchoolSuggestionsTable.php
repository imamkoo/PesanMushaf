<?php

namespace App\Filament\Resources\SchoolSuggestions\Tables;

use App\Models\District;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchoolSuggestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable()
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
                TextColumn::make('name')
                    ->label('Nama sekolah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('school_name_normalized')
                    ->label('Nama (normalized)')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
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
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
