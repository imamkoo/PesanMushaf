<?php

namespace App\Models;

use App\Support\SchoolNameNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    private ?int $originalBatchIdBeforeSave = null;

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

        static::updating(function (self $registration): void {
            if ($registration->isDirty('batch_id')) {
                $registration->originalBatchIdBeforeSave = $registration->getOriginal('batch_id');
            }
        });

        static::saved(function (self $registration): void {
            $registration->syncTouchedBatchFullness();
        });

        static::deleted(function (self $registration): void {
            $registration->syncCurrentBatchFullness();
        });

        static::restored(function (self $registration): void {
            $registration->syncCurrentBatchFullness();
        });

        static::forceDeleted(function (self $registration): void {
            $registration->syncCurrentBatchFullness();
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

    public function scopeWhereLegacyOrUnknownLevel(Builder $query): Builder
    {
        return $query->whereNull('education_level');
    }

    private function syncTouchedBatchFullness(): void
    {
        $batchIds = collect([
            $this->batch_id,
            $this->originalBatchIdBeforeSave,
        ])
            ->filter()
            ->unique()
            ->values();

        if ($batchIds->isEmpty()) {
            $this->originalBatchIdBeforeSave = null;

            return;
        }

        Batch::query()
            ->whereIn('id', $batchIds)
            ->get()
            ->each(fn (Batch $batch) => $batch->syncFullness());

        $this->originalBatchIdBeforeSave = null;
    }

    private function syncCurrentBatchFullness(): void
    {
        if ($this->batch_id === null) {
            return;
        }

        Batch::query()
            ->whereKey($this->batch_id)
            ->get()
            ->each(fn (Batch $batch) => $batch->syncFullness());
    }
}
