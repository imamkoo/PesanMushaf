<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'city',
    ];

    /**
     * Relasi: Satu universitas memiliki banyak pendaftar
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}