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
        $amount = $this->faker->randomFloat(2, 50, 1000);
        $currency = $this->faker->randomElement(['USD', 'EUR', 'GBP', 'TRY']);
        $exchangeRate = $currency === 'USD' ? 1.0 : $this->faker->randomFloat(6, 0.5, 2.0);
        $amountUsd = $amount * $exchangeRate;

        // Use the actual categories from the Transaction model
        $incomeCategories = ['SALES', 'PARTNER_REPAYMENT', 'INVESTMENT_RETURN', 'INVESTMENT_INCOME', 'OTHER_INCOME'];
        $expenseCategories = ['RETURNS', 'PAY-PRODUCT', 'PAY-DELIVERY', 'INVENTORY', 'WITHDRAW', 'BANK_FEE', 'FEE', 'ADS', 'OTHER_PAY'];

        $type = $isIncome ? 'INCOME' : 'EXPENSE';
        $category = $isIncome ? 
            $this->faker->randomElement($incomeCategories) : 
            $this->faker->randomElement($expenseCategories);

        return [
            'store_id' => Store::factory(),
            'created_by' => User::factory(),
            'transaction_id' => $this->faker->unique()->numerify('TXN-########'),
            'external_id' => $this->faker->optional()->numerify('EXT-########'),
            'reference_number' => $this->faker->optional()->numerify('REF-########'),
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'amount_usd' => $amountUsd,
            'type' => $type,
            'category' => $category,
            'description' => $this->faker->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => 'APPROVED', // Default to approved for testing
            'source' => 'manual',
            'metadata' => [
                'source_detail' => 'factory',
                'ip_address' => $this->faker->ipv4(),
            ],
            'is_reconciled' => $this->faker->boolean(80),
            'payment_processor_type' => $this->faker->optional()->randomElement(['STRIPE', 'PAYPAL', 'SHOPIFY_PAYMENTS']),
            'payment_processor_id' => null,
            'is_pending_payout' => $this->faker->boolean(20),
            'payout_date' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'is_personal_expense' => false,
            'partner_id' => null,
            'is_adjustment' => false,
            'adjustment_type' => null,
        ];
    }

    /**
     * Indicate that the transaction is a sale.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => $this->faker->randomFloat(2, 50, 500),
            'amount_usd' => $attributes['currency'] === 'USD' ? 
                $this->faker->randomFloat(2, 50, 500) : 
                $this->faker->randomFloat(2, 50, 500) * $attributes['exchange_rate'],
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EXPENSE',
            'category' => $this->faker->randomElement(['PAY-PRODUCT', 'ADS', 'FEE', 'OTHER_PAY']),
            'amount' => $this->faker->randomFloat(2, 10, 200),
            'amount_usd' => $attributes['currency'] === 'USD' ? 
                $this->faker->randomFloat(2, 10, 200) : 
                $this->faker->randomFloat(2, 10, 200) * $attributes['exchange_rate'],
        ]);
    }

    /**
     * Indicate that the transaction is a return.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EXPENSE',
            'category' => 'RETURNS',
            'amount' => $this->faker->randomFloat(2, 20, 300),
            'amount_usd' => $attributes['currency'] === 'USD' ? 
                $this->faker->randomFloat(2, 20, 300) : 
                $this->faker->randomFloat(2, 20, 300) * $attributes['exchange_rate'],
        ]);
    }
}
