<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductType>
 */
class ProductTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = [
            '#3b82f6', // Blue
            '#ef4444', // Red
            '#10b981', // Green
            '#f59e0b', // Amber
            '#8b5cf6', // Purple
            '#ec4899', // Pink
            '#14b8a6', // Teal
            '#f97316', // Orange
        ];

        return [
            'name' => fake()->words(2, true),
            'order' => fake()->numberBetween(0, 100),
            'show_on_front' => fake()->boolean(30), // 30% chance of being shown on front
            'color' => fake()->randomElement($colors),
            'site_id' => \App\Models\Site::factory(),
        ];
    }

    /**
     * Create a product type that shows on front.
     */
    public function showOnFront(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_on_front' => true,
        ]);
    }

    /**
     * Create a product type with specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Create a product type with specific color.
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
}
