<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partnership>
 */
class PartnershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'ownership_percentage' => $this->faker->randomFloat(2, 1, 50),
            'role' => $this->faker->randomElement(['owner', 'partner', 'investor', 'manager']),
            'role_description' => $this->faker->sentence(),
            'partnership_start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'partnership_end_date' => null,
            'status' => 'ACTIVE',
            'permissions' => [
                'view_analytics' => true,
                'manage_products' => false,
            ],
            'notes' => $this->faker->paragraph(),
        ];
    }

    /**
     * Indicate that the partnership is pending invitation.
     */
    public function pendingInvitation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING_INVITATION',
            'user_id' => null,
            'partner_email' => $this->faker->email(),
            'invitation_token' => bin2hex(random_bytes(32)),
            'invited_at' => now(),
        ]);
    }

    /**
     * Indicate that the partnership is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ACTIVE',
            'activated_at' => now(),
        ]);
    }

    /**
     * Indicate that the partnership is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'INACTIVE',
        ]);
    }
}