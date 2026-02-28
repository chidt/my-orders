<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->generateLocationCode(),
            'name' => $this->generateLocationName(),
            'is_default' => false,
            'warehouse_id' => Warehouse::factory(),
        ];
    }

    /**
     * Generate a realistic location code.
     */
    private function generateLocationCode(): string
    {
        $letters = ['A', 'B', 'C', 'D', 'E'];
        $letter = $this->faker->randomElement($letters);
        $number = $this->faker->numberBetween(1, 99);

        return $letter.str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a realistic location name.
     */
    private function generateLocationName(): string
    {
        $types = [
            'Kệ',
            'Shelf',
            'Vị trí',
            'Zone',
            'Khu vực',
            'Bay',
            'Tầng',
        ];

        $type = $this->faker->randomElement($types);
        $identifier = $this->faker->bothify('?#');

        return "{$type} {$identifier}";
    }

    /**
     * Mark location as default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Create location with specific code.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }

    /**
     * Create location for specific warehouse.
     */
    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(function (array $attributes) use ($warehouse) {
            return [
                'warehouse_id' => $warehouse->id,
            ];
        })->sequence(function ($sequence) {
            return [
                'code' => 'A'.str_pad($sequence->index + 1, 2, '0', STR_PAD_LEFT),
            ];
        });
    }
}
