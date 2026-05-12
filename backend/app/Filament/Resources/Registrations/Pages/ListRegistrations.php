<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = RegistrationResource::getEloquentQuery();

        return [
            'all' => Tab::make('Semua')
                ->badge((clone $baseQuery)->count())
                ->icon('heroicon-m-queue-list'),
            'umum' => Tab::make('UMUM')
                ->badge((clone $baseQuery)->whereUmum()->count())
                ->badgeColor('danger')
                ->icon('heroicon-m-identification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereUmum()),
            'non_umum' => Tab::make('Non-UMUM')
                ->badge((clone $baseQuery)->whereNonUmum()->count())
                ->badgeColor('primary')
                ->icon('heroicon-m-academic-cap')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNonUmum()),
        ];
    }
}
