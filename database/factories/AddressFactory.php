<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use App\Models\User;
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
            'addressable_id' => fn () => $this->getRandomAddressableId(),
            'addressable_type' => fn () => $this->getRandomAddressableType(),
            'ward_id' => fn () => $this->getRandomWardId(),
        ];
    }

    private ?string $cachedAddressableType = null;

    /**
     * Get a random existing addressable ID.
     * Creates a new model if none exists.
     */
    private function getRandomAddressableId(): int
    {
        $type = $this->getRandomAddressableType();

        return $this->getAddressableIdByType($type);
    }

    /**
     * Get addressable ID by type.
     */
    private function getAddressableIdByType(string $type): int
    {
        if ($type === Customer::class) {
            $model = Customer::inRandomOrder()->first();
            if (! $model) {
                $model = Customer::factory()->create();
            }
        } else {
            $model = User::inRandomOrder()->first();
            if (! $model) {
                $model = User::factory()->create();
            }
        }

        return $model->id;
    }

    /**
     * Get a random addressable type - cached per factory instance.
     */
    private function getRandomAddressableType(): string
    {
        if ($this->cachedAddressableType === null) {
            $this->cachedAddressableType = fake()->randomElement([Customer::class, User::class]);
        }

        return $this->cachedAddressableType;
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
            'addressable_id' => $customer instanceof Customer ? $customer->id : $customer,
            'addressable_type' => Customer::class,
        ]);
    }

    /**
     * Create an address for a specific user.
     */
    public function forUser(User|int $user): static
    {
        return $this->state(fn (array $attributes) => [
            'addressable_id' => $user instanceof User ? $user->id : $user,
            'addressable_type' => User::class,
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
