<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderDetail>
 */
class OrderDetailFactory extends Factory
{
    protected $model = OrderDetail::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 1000);
        $qty = fake()->numberBetween(1, 5);

        return [
            'payment_status' => 1,
            'status' => 1,
            'fulfillment_status' => 0,
            'qty' => $qty,
            'price' => $price,
            'discount' => 0,
            'addition_price' => 0,
            'total' => $qty * $price,
            'note' => fake()->optional()->sentence(),
            'product_item_id' => ProductItem::factory(),
            'order_id' => Order::factory(),
            'site_id' => fn (array $attributes) => Order::query()->find($attributes['order_id'])?->site_id,
        ];
    }
}
