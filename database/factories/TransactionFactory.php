<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isIncome = $this->faker->boolean(60); // 60% chance of income
        $amount = $isIncome ? 
            $this->faker->randomFloat(2, 50, 1000) : 
            -$this->faker->randomFloat(2, 10, 500);
        
        return [
            'store_id' => Store::factory(),
            'created_by' => User::factory(),
            'transaction_id' => $this->faker->unique()->numerify('TXN-########'),
            'external_id' => $this->faker->optional()->numerify('EXT-########'),
            'reference_number' => $this->faker->optional()->numerify('REF-########'),
            'amount' => $amount,
            'currency' => 'USD',
            'exchange_rate' => 1.000000,
            'amount_usd' => $amount,
            'type' => $isIncome ? 'income' : 'expense',
            'category' => $this->faker->randomElement([
                'revenue',
                'cost_of_goods',
                'marketing',
                'shipping',
                'fees_commissions',
                'taxes',
                'refunds_returns',
                'operational',
                'partnerships',
                'investments',
                'other'
            ]),
            'description' => $this->faker->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'source' => 'manual',
            'metadata' => [
                'source_detail' => 'factory',
                'ip_address' => $this->faker->ipv4(),
            ],
            'is_reconciled' => $this->faker->boolean(80),
            'payment_processor_type' => $this->faker->optional()->randomElement(['STRIPE', 'PAYPAL', 'SHOPIFY_PAYMENTS']),
            'payment_processor_id' => null, // Set to null to avoid foreign key constraint issues
            'is_pending_payout' => $this->faker->boolean(20),
            'payout_date' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'is_personal_expense' => $this->faker->boolean(10),
            'partner_id' => null,
            'is_adjustment' => $this->faker->boolean(5),
            'adjustment_type' => $this->faker->optional()->randomElement(['manual_correction', 'bank_reconciliation', 'import_correction']),
        ];
    }

    /**
     * Indicate that the transaction is a sale.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'category' => 'revenue',
            'amount' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'category' => 'operational',
            'amount' => -abs($this->faker->randomFloat(2, 10, 200)),
        ]);
    }

    /**
     * Indicate that the transaction is a return.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'category' => 'refunds_returns',
            'amount' => -abs($this->faker->randomFloat(2, 20, 300)),
        ]);
    }
}