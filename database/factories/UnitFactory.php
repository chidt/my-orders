<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = [
            ['name' => 'Piece', 'unit' => 'pcs'],
            ['name' => 'Kilogram', 'unit' => 'kg'],
            ['name' => 'Gram', 'unit' => 'g'],
            ['name' => 'Liter', 'unit' => 'l'],
            ['name' => 'Milliliter', 'unit' => 'ml'],
            ['name' => 'Meter', 'unit' => 'm'],
            ['name' => 'Centimeter', 'unit' => 'cm'],
            ['name' => 'Box', 'unit' => 'box'],
            ['name' => 'Pack', 'unit' => 'pack'],
            ['name' => 'Bottle', 'unit' => 'bottle'],
        ];

        $selectedUnit = $this->faker->randomElement($units);

        return [
            'name' => $selectedUnit['name'],
            'unit' => $selectedUnit['unit'],
        ];
    }

    /**
     * Create a piece unit.
     */
    public function piece(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Piece',
            'unit' => 'pcs',
        ]);
    }

    /**
     * Create a weight unit.
     */
    public function weight(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kilogram',
            'unit' => 'kg',
        ]);
    }

    /**
     * Create a volume unit.
     */
    public function volume(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Liter',
            'unit' => 'l',
        ]);
    }
}
