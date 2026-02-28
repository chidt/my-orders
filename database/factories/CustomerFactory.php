<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'type' => fake()->randomElement(CustomerType::cases())->value,
            'description' => fake()->sentence(10),
            'site_id' => fn () => $this->getRandomSiteId(),
        ];
    }

    /**
     * Get a random existing site ID.
     * Creates a new site if none exists.
     */
    private function getRandomSiteId(): int
    {
        $site = Site::inRandomOrder()->first();

        if (! $site) {
            // Create a site if none exists
            $site = Site::factory()->create();
        }

        return $site->id;
    }

    /**
     * Create a customer for a specific site.
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Create a customer of a specific type.
     */
    public function ofType(CustomerType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
        ]);
    }

    /**
     * Create an individual customer.
     */
    public function individual(): static
    {
        return $this->ofType(CustomerType::INDIVIDUAL);
    }

    /**
     * Create a business customer.
     */
    public function business(): static
    {
        return $this->ofType(CustomerType::BUSINESS);
    }

    /**
     * Create a corporate customer.
     */
    public function corporate(): static
    {
        return $this->ofType(CustomerType::CORPORATE);
    }

    /**
     * Create customer with a specific site ID and validate it exists.
     */
    public function withSiteId(int $siteId): static
    {
        if (! Site::where('id', $siteId)->exists()) {
            throw new \InvalidArgumentException("Site with ID {$siteId} does not exist.");
        }

        return $this->state(fn (array $attributes) => [
            'site_id' => $siteId,
        ]);
    }
}
