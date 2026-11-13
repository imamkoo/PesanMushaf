<?php

namespace App\Filament\Resources\ApiKeys\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiKeysTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('key')
                ->label('API Key')
                ->formatStateUsing(fn (?string $state): string => self::mask($state)),
            TextColumn::make('created_at')->dateTime(),
        ]);
    }

    public static function mask(?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        $visible = min(10, intdiv(mb_strlen($key), 3));

        return mb_substr($key, 0, $visible).str_repeat('•', 8).mb_substr($key, -4);
    }
}
