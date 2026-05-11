<?php

namespace App\Models;

use App\Support\SchoolNameNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exclude_from_school_suggestions' => 'boolean',
            'whatsapp_payment_notified_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'batch_id',
        'district_id',
        'university_id',
        'education_level',
        'edition',
        'name',
        'phone_number',
        'email',
        'nik',
        'address',
        'school_name',
        'school_name_normalized',
        'exclude_from_school_suggestions',
        'registration_code', 
        'page_number', 
        'base_price',
        'total_payment',
        'payment_status',
        'whatsapp_payment_notified_at',
        'payment_receipt',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $registration): void {
            if ($registration->isDirty('school_name') || $registration->school_name_normalized === null) {
                $registration->school_name_normalized = SchoolNameNormalizer::normalize($registration->school_name);
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
    
    // Jika Anda ingin memastikan district juga terhubung
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}