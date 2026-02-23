<?php

namespace App\Models;

use App\Concerns\HasAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasAddress, HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'description',
        'site_id',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
