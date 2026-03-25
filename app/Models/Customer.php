<?php

namespace App\Models;

use App\Concerns\HasAddress;
use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasAddress, HasFactory;

    protected $appends = [
        'address',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'description',
        'site_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
        ];
    }

    public static function typeOptions(): array
    {
        return [
            CustomerType::INDIVIDUAL->value => 'Cá nhân',
            CustomerType::BUSINESS->value => 'Doanh nghiệp',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function getAddressAttribute(): ?string
    {
        $defaultAddress = $this->addresses->firstWhere('is_default', true)
            ?? $this->addresses->first();

        if (! $defaultAddress) {
            return null;
        }

        $parts = array_filter([
            $defaultAddress->address,
            $defaultAddress->ward?->name,
            $defaultAddress->ward?->province?->name,
        ]);

        return implode(', ', $parts);
    }
}
