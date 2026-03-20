<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'person_in_charge' => $this->faker->optional(0.8)->name(),
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'address' => $this->faker->optional(0.6)->address(),
            'description' => $this->faker->optional(0.5)->paragraph(),
            'site_id' => Site::factory(),
        ];
    }

    /**
     * Create a supplier for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Create a supplier with complete contact information.
     */
    public function withFullContact(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_in_charge' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
        ]);
    }
}
