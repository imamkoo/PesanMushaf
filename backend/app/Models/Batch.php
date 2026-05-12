<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'district_id', 'name', 'slug', 'batch_number',
        'education_level', 'max_capacity', 'is_full',
    ];

    protected function casts(): array
    {
        return [
            'district_id' => 'integer',
            'max_capacity' => 'integer',
            'is_full' => 'boolean',
        ];
    }

    public function scopeWithActiveRegistrationsCount(Builder $query): Builder
    {
        return $query->withCount('registrations');
    }

    public function scopeWhereFullByOccupancy(Builder $query, bool $isFull = true): Builder
    {
        $batchTable = $query->getModel()->getTable();
        $operator = $isFull ? '>=' : '<';

        return $query->whereRaw(
            $this->getActiveRegistrationsCountSubquery($query)." {$operator} {$batchTable}.max_capacity"
        );
    }

    public function scopeWhereOccupancyBetween(
        Builder $query,
        ?float $minRatio = null,
        ?float $maxRatio = null,
    ): Builder {
        $batchTable = $query->getModel()->getTable();
        $activeRegistrationsCount = $this->getActiveRegistrationsCountSubquery($query);

        $query->where("{$batchTable}.max_capacity", '>', 0);

        if ($minRatio !== null) {
            $query->whereRaw("{$activeRegistrationsCount} >= {$batchTable}.max_capacity * ?", [$minRatio]);
        }

        if ($maxRatio !== null) {
            $query->whereRaw("{$activeRegistrationsCount} < {$batchTable}.max_capacity * ?", [$maxRatio]);
        }

        return $query;
    }

    public function scopeWhereUmum(Builder $query): Builder
    {
        return $query->where('education_level', 'UMUM');
    }

    public function scopeWhereNonUmum(Builder $query): Builder
    {
        return $query
            ->whereNotNull('education_level')
            ->where('education_level', '!=', 'UMUM');
    }

    public function scopeWhereGlobalOrNull(Builder $query): Builder
    {
        return $query->whereNull('education_level');
    }

    public function scopeOrderByOccupancy(Builder $query, string $direction = 'desc'): Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderByRaw($this->getOccupancyRatioExpression($query)." {$direction}");
    }

    public function activeRegistrationsCount(): int
    {
        return $this->registrations()->count();
    }

    public function occupancyRatio(?int $registrationsCount = null): float
    {
        if ($this->max_capacity <= 0) {
            return 0.0;
        }

        $registrationsCount ??= $this->registrations_count !== null
            ? (int) $this->registrations_count
            : $this->activeRegistrationsCount();

        return $registrationsCount / $this->max_capacity;
    }

    public function fillPercentage(?int $registrationsCount = null): int
    {
        return (int) min(100, round($this->occupancyRatio($registrationsCount) * 100));
    }

    public function isFullByOccupancy(?int $registrationsCount = null): bool
    {
        $registrationsCount ??= $this->registrations_count !== null
            ? (int) $this->registrations_count
            : $this->activeRegistrationsCount();

        return $registrationsCount >= $this->max_capacity;
    }

    public function syncFullness(?int $registrationsCount = null): bool
    {
        $isFull = $this->isFullByOccupancy($registrationsCount);

        if ($this->is_full !== $isFull) {
            $this->forceFill(['is_full' => $isFull])->saveQuietly();
        }

        $this->is_full = $isFull;

        return $isFull;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function isVipGlobal(): bool
    {
        return $this->district_id === null && str_contains((string) $this->name, '(GOR)');
    }

    protected function getActiveRegistrationsCountSubquery(Builder $query): string
    {
        $batchTable = $query->getModel()->getTable();
        $registrationTable = (new Registration)->getTable();

        return "(select count(*) from {$registrationTable} where {$registrationTable}.batch_id = {$batchTable}.id and {$registrationTable}.deleted_at is null)";
    }

    protected function getOccupancyRatioExpression(Builder $query): string
    {
        $batchTable = $query->getModel()->getTable();

        return 'coalesce((1.0 * '.$this->getActiveRegistrationsCountSubquery($query).") / nullif({$batchTable}.max_capacity, 0), 0)";
    }
}
