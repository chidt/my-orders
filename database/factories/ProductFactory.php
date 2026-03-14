<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $purchasePrice = $this->faker->randomFloat(2, 10, 500);
        $price = $purchasePrice * $this->faker->randomFloat(2, 1.2, 2.5); // 20-150% markup
        $partnerPrice = $price * $this->faker->randomFloat(2, 0.8, 0.95); // 5-20% discount

        return [
            'name' => $name,
            'code' => strtoupper($this->faker->unique()->bothify('PRD-###-???')),
            'supplier_code' => $this->faker->optional(0.7)->bothify('SUP-###'),
            'product_type_id' => $this->faker->numberBetween(1, 3), // Assuming 1-3 are valid product type IDs
            'description' => $this->faker->optional(0.8)->paragraph(),
            'qty_in_stock' => $this->faker->numberBetween(0, 1000),
            'weight' => $this->faker->optional(0.6)->randomFloat(2, 0.1, 50),
            'price' => $price,
            'partner_price' => $partnerPrice,
            'purchase_price' => $purchasePrice,
            'supplier_id' => Supplier::factory(),
            'unit_id' => Unit::factory(),
            'category_id' => Category::factory(),
            'order_closing_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'default_location_id' => Location::factory(),
            'site_id' => Site::factory(),
            'extra_attributes' => $this->faker->optional(0.4)->randomElements([
                'color' => $this->faker->colorName(),
                'brand' => $this->faker->company(),
                'origin' => $this->faker->country(),
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    /**
     * Create a product for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Create a product with a specific supplier.
     */
    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
            'site_id' => $supplier->site_id,
        ]);
    }

    /**
     * Create a product in a specific category.
     */
    public function inCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
            'site_id' => $category->site_id,
        ]);
    }

    /**
     * Create an out of stock product.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'qty_in_stock' => 0,
        ]);
    }

    /**
     * Create a product with preorder deadline.
     */
    public function withPreorderDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_closing_date' => $this->faker->dateTimeBetween('now', '+14 days'),
        ]);
    }

    /**
     * Create a premium product with higher prices.
     */
    public function premium(): static
    {
        $purchasePrice = $this->faker->randomFloat(2, 100, 1000);
        $price = $purchasePrice * $this->faker->randomFloat(2, 2, 3); // Higher markup

        return $this->state(fn (array $attributes) => [
            'purchase_price' => $purchasePrice,
            'price' => $price,
            'partner_price' => $price * 0.9,
        ]);
    }
}
