<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'addressable_id',
        'addressable_type',
        'ward_id',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
