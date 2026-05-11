<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class District extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'photo', 'code'];

    // Mutator agar slug terisi otomatis jika admin input manual
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function schoolSuggestions(): HasMany
    {
        return $this->hasMany(SchoolSuggestion::class);
    }
}