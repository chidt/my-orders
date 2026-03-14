<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tags = [
            'new', 'hot', 'sale', 'featured', 'popular', 'trending',
            'limited', 'organic', 'premium', 'eco-friendly', 'bestseller',
            'seasonal', 'imported', 'local', 'handmade', 'artisan',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($tags),
            'site_id' => Site::factory(),
        ];
    }

    /**
     * Create a tag for a specific site.
     */
    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    /**
     * Create a promotional tag.
     */
    public function promotional(): static
    {
        $promoTags = ['sale', 'discount', 'special offer', 'flash sale', 'clearance'];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($promoTags),
        ]);
    }
}
