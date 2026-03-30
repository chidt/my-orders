<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Province;
use App\Models\Site;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $site = Site::factory()->create();
        $customer = Customer::factory()->forSite($site)->create();
        $province = Province::factory()->create();
        $ward = Ward::factory()->create(['province_id' => $province->id]);
        $address = Address::factory()
            ->forCustomer($customer)
            ->forWard($ward)
            ->create();

        return [
            'payment_status' => 1,
            'order_number' => 'ORD-'.fake()->unique()->numberBetween(1000000000, 9999999999),
            'order_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'customer_type' => $customer->type?->value ?? (int) $customer->type,
            'status' => 1,
            'shipping_payer' => 1,
            'phone' => $customer->phone,
            'shipping_note' => fake()->optional()->sentence(),
            'order_note' => fake()->optional()->sentence(),
            'sale_channel' => fake()->numberBetween(1, 3),
            'shipping_address_id' => $address->id,
            'customer_id' => $customer->id,
            'site_id' => $site->id,
        ];
    }
}
