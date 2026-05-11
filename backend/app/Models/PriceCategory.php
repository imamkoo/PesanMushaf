<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PriceCategory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'amount' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected $fillable = [
        'slug',
        'name',
        'amount',
        'is_active',
        'sort_order',
    ];

    /**
     * @param  Builder<PriceCategory>  $query
     * @return Builder<PriceCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
