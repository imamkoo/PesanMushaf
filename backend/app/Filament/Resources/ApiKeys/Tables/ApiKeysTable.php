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
            TextColumn::make('key')->copyable()->label('API Key'),
            TextColumn::make('created_at')->dateTime(),
        ]);
    }
}
