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
        $registrationTable = (new Registration)->getTable();
        $operator = $isFull ? '>=' : '<';

        return $query->whereRaw(
            "(select count(*) from {$registrationTable} where {$registrationTable}.batch_id = {$batchTable}.id and {$registrationTable}.deleted_at is null) {$operator} {$batchTable}.max_capacity"
        );
    }

    public function activeRegistrationsCount(): int
    {
        return $this->registrations()->count();
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
}
