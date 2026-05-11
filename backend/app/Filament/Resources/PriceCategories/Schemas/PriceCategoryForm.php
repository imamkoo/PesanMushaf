<?php

namespace App\Filament\Resources\PriceCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PriceCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kategori harga')
                ->description('Slug dipakai API pendaftaran (contoh: reguler, vip). Untuk kategori baru, sesuaikan juga formulir publik bila perlu.')
                ->schema([
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(32)
                        ->regex('/^[a-z0-9_]+$/')
                        ->helperText('Huruf kecil, angka, dan underscore. Harus unik.')
                        ->unique(table: 'price_categories', column: 'slug', ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama tampilan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('amount')
                        ->label('Harga (IDR)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('Rp'),
                    TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }
}
