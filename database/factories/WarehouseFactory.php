<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->generateWarehouseCode(),
            'name' => $this->generateWarehouseName(),
            'address' => $this->faker->address,
            'site_id' => Site::factory(),
        ];
    }

    /**
     * Generate a realistic warehouse code.
     * Note: This should only be used when not using forSite() method.
     */
    private function generateWarehouseCode(): string
    {
        $prefixes = ['W', 'KHO', 'WH'];
        $prefix = $this->faker->randomElement($prefixes);

        // Generate a random number for default case
        // When forSite() is used, this will be overridden
        $number = $this->faker->numberBetween(1, 999);

        return $prefix.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a realistic warehouse name.
     */
    private function generateWarehouseName(): string
    {
        $types = [
            'Kho trung tâm',
            'Kho chính',
            'Kho phụ',
            'Kho tổng',
            'Warehouse',
            'Storage',
            'Depot',
        ];

        $locations = [
            'Hà Nội',
            'TP.HCM',
            'Đà Nẵng',
            'Hải Phòng',
            'Cần Thơ',
            'Quận 1',
            'Quận 7',
            'Tân Bình',
        ];

        $type = $this->faker->randomElement($types);
        $location = $this->faker->randomElement($locations);

        return "{$type} {$location}";
    }

    /**
     * Create warehouse for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(function (array $attributes) use ($site) {
            return [
                'site_id' => $site->id,
            ];
        })->sequence(function ($sequence) {
            return [
                'code' => 'W'.str_pad($sequence->index + 1, 3, '0', STR_PAD_LEFT),
            ];
        });
    }

    /**
     * Create warehouse with a specific code.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }
}
