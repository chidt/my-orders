<?php

namespace App\Support;

use App\Enums\CustomerType;
use App\Models\Address;
use App\Models\Customer;

final class OrderCustomerPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function forSearch(Customer $customer): array
    {
        $customer->loadMissing(['addresses.ward.province']);

        $type = $customer->type;
        $typeValue = $type instanceof CustomerType ? $type->value : (int) $customer->getRawOriginal('type');

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'type' => $typeValue,
            'addresses' => $customer->addresses->map(function (Address $address) {
                return [
                    'id' => $address->id,
                    'address' => $address->address,
                    'is_default' => (bool) $address->is_default,
                    'ward' => $address->ward?->name,
                    'province' => $address->ward?->province?->name,
                ];
            })->values()->all(),
        ];
    }
}
