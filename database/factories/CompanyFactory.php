<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
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
            'slug' => $this->faker->unique()->slug(),
            'domain' => $this->faker->domainName(),
            'description' => $this->faker->paragraph(),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'timezone' => $this->faker->timezone(),
            'currency' => 'USD',
            'settings' => [
                'theme' => 'light',
                'notifications' => true,
            ],
            'status' => 'active',
            'plan' => $this->faker->randomElement(['starter', 'professional', 'enterprise']),
            'plan_expires_at' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'is_trial' => $this->faker->boolean(30), // 30% chance of trial
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+14 days'),
        ];
    }
}
