<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attributes = [
            ['name' => 'Color', 'code' => 'color'],
            ['name' => 'Size', 'code' => 'size'],
            ['name' => 'Material', 'code' => 'material'],
            ['name' => 'Brand', 'code' => 'brand'],
            ['name' => 'Weight', 'code' => 'weight'],
            ['name' => 'Length', 'code' => 'length'],
            ['name' => 'Width', 'code' => 'width'],
            ['name' => 'Height', 'code' => 'height'],
        ];

        $selectedAttribute = $this->faker->randomElement($attributes);
        $uniqueSuffix = (string) $this->faker->unique()->numberBetween(1000, 999999);

        return [
            'name' => $selectedAttribute['name'].' '.$uniqueSuffix,
            'code' => $selectedAttribute['code'].'-'.$uniqueSuffix,
            'description' => $this->faker->optional(0.6)->sentence(),
            'order' => $this->faker->numberBetween(1, 100),
            'site_id' => Site::factory(),
        ];
    }

    /**
     * Create an attribute for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Create a color attribute.
     */
    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Color',
            'code' => 'color',
        ]);
    }

    /**
     * Create a size attribute.
     */
    public function size(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Size',
            'code' => 'size',
        ]);
    }
}
