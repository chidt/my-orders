<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Site;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $fakerVi = FakerFactory::create('vi_VN');
        $type = fake()->randomElement(CustomerType::cases())->value;

        return [
            'name' => $type === CustomerType::INDIVIDUAL->value ? $fakerVi->name() : $fakerVi->company(),
            'phone' => fake()->phoneNumber(),
            'email' => $type === CustomerType::INDIVIDUAL->value ? $fakerVi->unique()->safeEmail() : $fakerVi->unique()->companyEmail(),
            'type' => $type,
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
