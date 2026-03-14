<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductItem>
 */
class ProductItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 10, 500);
        $price = $purchasePrice * $this->faker->randomFloat(2, 1.2, 2.5);
        $partnerPrice = $price * $this->faker->randomFloat(2, 0.8, 0.95);

        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-###-???-###')),
            'is_parent_image' => $this->faker->boolean(20), // 20% chance to be parent image
            'is_parent_slider_image' => $this->faker->boolean(10), // 10% chance to be slider image
            'qty_in_stock' => $this->faker->numberBetween(0, 500),
            'price' => $price,
            'partner_price' => $partnerPrice,
            'purchase_price' => $purchasePrice,
            'product_id' => Product::factory(),
            'site_id' => Site::factory(),
        ];
    }

    /**
     * Create a product item for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'site_id' => $product->site_id,
        ]);
    }

    /**
     * Create a product item for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Create a product item as the parent image.
     */
    public function asParentImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_parent_image' => true,
        ]);
    }

    /**
     * Create a product item as slider image.
     */
    public function asSliderImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_parent_slider_image' => true,
        ]);
    }

    /**
     * Create an out of stock product item.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'qty_in_stock' => 0,
        ]);
    }

    /**
     * Create a product item with high stock.
     */
    public function highStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'qty_in_stock' => $this->faker->numberBetween(1000, 5000),
        ]);
    }

    /**
     * Create a premium product item.
     */
    public function premium(): static
    {
        $purchasePrice = $this->faker->randomFloat(2, 100, 1000);
        $price = $purchasePrice * $this->faker->randomFloat(2, 2, 3);

        return $this->state(fn (array $attributes) => [
            'purchase_price' => $purchasePrice,
            'price' => $price,
            'partner_price' => $price * 0.9,
        ]);
    }
}
