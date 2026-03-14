<?php

namespace Database\Factories;

use App\Models\ProductAttributeValue;
use App\Models\ProductItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductItemAttributeValue>
 */
class ProductItemAttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_item_id' => ProductItem::factory(),
            'product_attribute_value_id' => ProductAttributeValue::factory(),
        ];
    }

    /**
     * Create a product item attribute value for a specific product item.
     */
    public function forProductItem(ProductItem $productItem): static
    {
        return $this->state(fn (array $attributes) => [
            'product_item_id' => $productItem->id,
        ]);
    }

    /**
     * Create a product item attribute value for a specific product attribute value.
     */
    public function forProductAttributeValue(ProductAttributeValue $productAttributeValue): static
    {
        return $this->state(fn (array $attributes) => [
            'product_attribute_value_id' => $productAttributeValue->id,
        ]);
    }

    /**
     * Create a product item attribute value for both specific item and attribute value.
     */
    public function for(ProductItem $productItem, ProductAttributeValue $productAttributeValue): static
    {
        return $this->state(fn (array $attributes) => [
            'product_item_id' => $productItem->id,
            'product_attribute_value_id' => $productAttributeValue->id,
        ]);
    }
}
