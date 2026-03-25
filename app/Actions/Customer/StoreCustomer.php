<?php

namespace App\Actions\Customer;

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
                $first = $addresses[0] ?? ['address' => $data['address'] ?? '', 'ward_id' => (int) ($data['ward_id'] ?? 0), 'is_default' => 1];

                $customer->addresses()->create([
                    'address' => $first['address'],
                    'ward_id' => (int) $first['ward_id'],
                    'is_default' => 1,
                ]);

                return $customer;
            }

            $defaultIndex = collect($addresses)->search(fn (array $address) => ($address['is_default'] ?? 0) === 1);
            if ($defaultIndex === false) {
                $defaultIndex = 0;
            }

            foreach ($addresses as $index => $address) {
                $customer->addresses()->create([
                    'address' => $address['address'],
                    'ward_id' => (int) $address['ward_id'],
                    'is_default' => $index === $defaultIndex ? 1 : 0,
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
                    'is_default' => $this->toBinary($item['is_default'] ?? 0),
                ];
            }, array_filter($data['addresses'], fn ($item) => ! empty($item['address']) && ! empty($item['ward_id']))));
        }

        return [[
            'address' => (string) ($data['address'] ?? ''),
            'ward_id' => (int) ($data['ward_id'] ?? 0),
            'is_default' => 1,
        ]];
    }

    private function toBinary(mixed $value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on'], true) ? 1 : 0;
    }
}
