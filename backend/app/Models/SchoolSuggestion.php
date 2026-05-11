<?php

namespace App\Models;

use App\Support\SchoolNameNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSuggestion extends Model
{
    protected $fillable = [
        'district_id',
        'education_level',
        'name',
        'school_name_normalized',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $suggestion): void {
            if ($suggestion->isDirty('name') || $suggestion->school_name_normalized === null) {
                $suggestion->school_name_normalized = SchoolNameNormalizer::normalize($suggestion->name);
            }
        });
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
