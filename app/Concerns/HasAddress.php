<?php

namespace App\Concerns;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAddress
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    public function setDefaultAddress(Address $address): void
    {
        $this->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    public function setAddressAsDefault(int $addressId): void
    {
        $this->addresses()->update(['is_default' => false]);
        $this->addresses()->where('id', $addressId)->update(['is_default' => true]);
    }
}
