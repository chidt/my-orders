<?php

namespace App\Actions\Customer;

use App\Enums\AddressDefaultStatus;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class StoreCustomer
{
    public function execute(array $data, Site $site): Customer
    {
        return DB::transaction(function () use ($data, $site): Customer {
            $customer = new Customer([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'type' => (int) $data['type'],
                'description' => $data['description'] ?? null,
            ]);

            $customer->site()->associate($site);
            $customer->save();

            $addresses = $this->normalizeAddresses($data);
            $type = (int) $data['type'];

            if ($type === CustomerType::INDIVIDUAL->value) {
                $first = $addresses[0] ?? [
                    'address' => $data['address'] ?? '',
                    'ward_id' => (int) ($data['ward_id'] ?? 0),
                    'is_default' => AddressDefaultStatus::DEFAULT->value,
                ];

                $customer->addresses()->create([
                    'address' => $first['address'],
                    'ward_id' => (int) $first['ward_id'],
                    'is_default' => AddressDefaultStatus::DEFAULT->value,
                ]);

                return $customer;
            }

            $defaultIndex = collect($addresses)
                ->search(fn (array $address) => ($address['is_default'] ?? AddressDefaultStatus::NOT_DEFAULT->value) === AddressDefaultStatus::DEFAULT->value);

            if ($defaultIndex === false) {
                $defaultIndex = 0;
            }

            foreach ($addresses as $index => $address) {
                $customer->addresses()->create([
                    'address' => $address['address'],
                    'ward_id' => (int) $address['ward_id'],
                    'is_default' => $index === $defaultIndex
                        ? AddressDefaultStatus::DEFAULT->value
                        : AddressDefaultStatus::NOT_DEFAULT->value,
                ]);
            }

            return $customer;
        });
    }

    private function normalizeAddresses(array $data): array
    {
        if (! empty($data['addresses']) && is_array($data['addresses'])) {
            return array_values(array_map(function (array $item): array {
                return [
                    'address' => (string) ($item['address'] ?? ''),
                    'ward_id' => (int) ($item['ward_id'] ?? 0),
                    'is_default' => $this->toBinary($item['is_default'] ?? AddressDefaultStatus::NOT_DEFAULT->value),
                ];
            }, array_filter($data['addresses'], fn ($item) => ! empty($item['address']) && ! empty($item['ward_id']))));
        }

        return [[
            'address' => (string) ($data['address'] ?? ''),
            'ward_id' => (int) ($data['ward_id'] ?? 0),
            'is_default' => AddressDefaultStatus::DEFAULT->value,
        ]];
    }

    private function toBinary(mixed $value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on'], true)
            ? AddressDefaultStatus::DEFAULT->value
            : AddressDefaultStatus::NOT_DEFAULT->value;
    }
}
