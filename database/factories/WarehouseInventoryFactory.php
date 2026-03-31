<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ProductItem;
use App\Models\WarehouseInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseInventory>
 */
class WarehouseInventoryFactory extends Factory
{
    protected $model = WarehouseInventory::class;

    public function definition(): array
    {
        return [
            'product_item_id' => ProductItem::factory(),
            'location_id' => Location::factory(),
            'current_qty' => fake()->numberBetween(0, 200),
            'reserved_qty' => 0,
            'pre_order_qty' => 0,
            'avg_cost' => fake()->randomFloat(2, 10, 500),
            'site_id' => fn (array $attributes) => ProductItem::query()->find($attributes['product_item_id'])?->site_id,
            'metadata' => null,
            'last_updated' => now(),
        ];
    }
}
