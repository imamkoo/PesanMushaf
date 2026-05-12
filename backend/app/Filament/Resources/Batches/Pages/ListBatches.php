<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = BatchResource::getEloquentQuery();

        return [
            'all' => Tab::make('Semua')
                ->badge((clone $baseQuery)->count())
                ->icon('heroicon-m-queue-list'),
            'umum' => Tab::make('Batch UMUM')
                ->badge((clone $baseQuery)->whereUmum()->count())
                ->badgeColor('danger')
                ->icon('heroicon-m-identification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereUmum()),
            'non_umum' => Tab::make('Batch Non-UMUM')
                ->badge((clone $baseQuery)->whereNonUmum()->count())
                ->badgeColor('primary')
                ->icon('heroicon-m-academic-cap')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNonUmum()),
            'global_null' => Tab::make('VIP Global')
                ->badge((clone $baseQuery)->whereGlobalOrNull()->count())
                ->badgeColor('gray')
                ->icon('heroicon-m-globe-asia-australia')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereGlobalOrNull()),
        ];
    }
}
