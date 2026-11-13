<?php

namespace App\Models;

use App\Models\Concerns\ResolvesSafeRouteBinding;
use App\Support\SchoolNameNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mosque extends Model
{
    use ResolvesSafeRouteBinding, SoftDeletes;

    protected $fillable = [
        'dmi_code',
        'external_ids',
        'name',
        'name_normalized',
        'address',
        'village',
        'district_name',
        'city',
        'province',
        'latitude',
        'longitude',
        'phone',
        'source',
        'source_url',
        'is_verified',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'external_ids' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $mosque): void {
            $mosque->name_normalized = SchoolNameNormalizer::normalize($mosque->name);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $normalized = SchoolNameNormalizer::normalize($term);

        if ($normalized === null) {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalized);

        return $query->where('name_normalized', 'like', '%'.$escaped.'%');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
