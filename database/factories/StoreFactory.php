<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company().' Store',
            'shopify_domain' => $this->faker->unique()->word().'.myshopify.com',
            'shopify_store_id' => $this->faker->numerify('########'),
            'shopify_access_token' => $this->faker->optional()->regexify('[A-Za-z0-9]{20}'),
            'currency' => 'USD',
            'country_code' => 'US',
            'timezone' => $this->faker->timezone(),
            'description' => $this->faker->paragraph(),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'status' => 'active',
            'last_sync_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'sync_errors' => $this->faker->optional()->paragraph(),
            'settings' => [
                'auto_sync' => $this->faker->boolean(),
                'sync_interval' => $this->faker->randomElement(['hourly', 'daily', 'weekly']),
            ],
        ];
    }
}
