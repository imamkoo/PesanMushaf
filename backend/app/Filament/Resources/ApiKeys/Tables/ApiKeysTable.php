<?php

namespace App\Filament\Resources\ApiKeys\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

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