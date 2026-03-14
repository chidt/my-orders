<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('VAL-###-???')),
            'value' => $this->faker->word(),
            'order' => $this->faker->numberBetween(1, 100),
            'purchase_addition_value' => $this->faker->optional(0.4)->randomFloat(2, 0, 50),
            'partner_addition_value' => $this->faker->optional(0.4)->randomFloat(2, 0, 30),
            'addition_value' => $this->faker->optional(0.4)->randomFloat(2, 0, 100),
            'product_id' => Product::factory(),
            'attribute_id' => Attribute::factory(),
        ];
    }

    /**
     * Create a product attribute value for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Create a product attribute value for a specific attribute.
     */
    public function forAttribute(Attribute $attribute): static
    {
        return $this->state(fn (array $attributes) => [
            'attribute_id' => $attribute->id,
        ]);
    }

    /**
     * Create a color attribute value.
     */
    public function colorValue(): static
    {
        $colors = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White', 'Purple', 'Orange', 'Pink', 'Brown'];

        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomElement($colors),
        ]);
    }

    /**
     * Create a size attribute value.
     */
    public function sizeValue(): static
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38'];

        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomElement($sizes),
        ]);
    }

    /**
     * Create a product attribute value with premium pricing.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_addition_value' => $this->faker->randomFloat(2, 20, 100),
            'partner_addition_value' => $this->faker->randomFloat(2, 15, 80),
            'addition_value' => $this->faker->randomFloat(2, 50, 200),
        ]);
    }
}
