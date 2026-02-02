<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address' => fake()->streetAddress().', '.fake()->city(),
            'customer_id' => fn () => $this->getRandomCustomerId(),
            'ward_id' => fn () => $this->getRandomWardId(),
        ];
    }

    /**
     * Get a random existing customer ID.
     * Creates a new customer if none exists.
     */
    private function getRandomCustomerId(): int
    {
        $customer = Customer::inRandomOrder()->first();

        if (! $customer) {
            // Create a customer if none exists
            $customer = Customer::factory()->create();
            // Note: Command is not available in factory context, so we skip logging
        }

        return $customer->id;
    }

    /**
     * Get a random existing ward ID.
     * Throws exception if no wards exist.
     */
    private function getRandomWardId(): int
    {
        $ward = Ward::inRandomOrder()->first();

        if (! $ward) {
            throw new \RuntimeException(
                'No existing wards found. Please run the ward import command (e.g., php artisan vnzone:import) before creating addresses.'
            );
        }

        return $ward->id;
    }

    /**
     * Create an address for a specific customer.
     */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /**
     * Create an address for a specific ward.
     */
    public function forWard(Ward|int $ward): static
    {
        return $this->state(fn (array $attributes) => [
            'ward_id' => $ward instanceof Ward ? $ward->id : $ward,
        ]);
    }

    /**
     * Create an address with a specific ward ID and validate it exists.
     */
    public function withWardId(int $wardId): static
    {
        if (! Ward::where('id', $wardId)->exists()) {
            throw new \InvalidArgumentException("Ward with ID {$wardId} does not exist.");
        }

        return $this->state(fn (array $attributes) => [
            'ward_id' => $wardId,
        ]);
    }
}
