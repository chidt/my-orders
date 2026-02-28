<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->unique()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'customer_id' => fn () => $this->getRandomCustomerId(),
            'site_id' => fn () => $this->getRandomSiteId(),
        ];
    }

    /**
     * Get a random existing customer ID that's not already assigned to a user.
     * Returns null if no available customers exist.
     */
    private function getRandomCustomerId(): ?int
    {
        // Get customers that don't have a user yet
        $availableCustomer = Customer::whereNotIn('id', function ($query) {
            $query->select('customer_id')->from('users')->whereNotNull('customer_id');
        })->inRandomOrder()->first();

        return $availableCustomer?->id;
    }

    /**
     * Get a random existing site ID.
     * Returns null if no sites exist.
     */
    private function getRandomSiteId(): ?int
    {
        $site = Site::inRandomOrder()->first();

        return $site?->id;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Create a user for a specific customer.
     */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /**
     * Create a user for a specific site.
     */
    public function forSite(Site|int $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site instanceof Site ? $site->id : $site,
        ]);
    }

    /**
     * Create a user with a specific customer ID and validate it exists.
     */
    public function withCustomerId(int $customerId): static
    {
        if (! Customer::where('id', $customerId)->exists()) {
            throw new \InvalidArgumentException("Customer with ID {$customerId} does not exist.");
        }

        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Create a user with a specific site ID and validate it exists.
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

    /**
     * Create a standalone user without customer association.
     */
    public function withoutCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }

    /**
     * Create a standalone user without site association.
     */
    public function withoutSite(): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => null,
        ]);
    }
}
