<?php

namespace App\Models;

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
