<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'person_in_charge',
        'phone',
        'address',
        'description',
        'site_id',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->products()->exists();
    }
}
