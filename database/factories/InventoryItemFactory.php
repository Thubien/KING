<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unitCost = $this->faker->randomFloat(2, 1, 100);
        $totalValue = $quantity * $unitCost;

        return [
            'store_id' => Store::factory(),
            'sku' => $this->faker->unique()->bothify('SKU-####-??'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_value' => $totalValue,
            'reorder_point' => $this->faker->numberBetween(5, 20),
            'location' => $this->faker->optional()->randomElement(['Warehouse A', 'Warehouse B', 'Store Front']),
            'is_active' => true,
            'last_counted_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'metadata' => [
                'supplier' => $this->faker->company(),
                'category' => $this->faker->randomElement(['Electronics', 'Clothing', 'Home', 'Sports']),
            ],
        ];
    }

    /**
     * Indicate that the inventory item is low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 5),
            'reorder_point' => $this->faker->numberBetween(10, 20),
        ]);
    }

    /**
     * Indicate that the inventory item is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'total_value' => 0,
        ]);
    }

    /**
     * Indicate that the inventory item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}