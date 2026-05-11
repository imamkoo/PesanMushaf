<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['photo', 'batch_id'];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
