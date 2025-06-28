<?php

namespace App\Services\Import\Parsers;

use InvalidArgumentException;
use App\Services\Import\Detectors\BankFormatDetector;

class AmountParser
{
    /**
     * Parse amount based on detected format and currency
     */
    public function parseAmount($rawAmount, string $format, ?string $currency = null): float
    {
        if ($rawAmount === null || $rawAmount === '') {
            return 0.0;
        }

        try {
            return match($format) {
                BankFormatDetector::FORMAT_MERCURY => $this->parseMercuryAmount($rawAmount),
                BankFormatDetector::FORMAT_PAYONEER => $this->parsePayoneerAmount($rawAmount, $currency),
                BankFormatDetector::FORMAT_STRIPE_BALANCE => $this->parseStripeAmount($rawAmount),
                BankFormatDetector::FORMAT_STRIPE_PAYMENTS => $this->parseStripeAmount($rawAmount),
                default => throw new InvalidArgumentException("Unknown amount format: {$format}")
            };
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Failed to parse amount '{$rawAmount}' for format '{$format}': " . $e->getMessage());
        }
    }

    /**
     * Parse Mercury Bank amount: float values (can be negative)
     */
    private function parseMercuryAmount($rawAmount): float
    {
        // Mercury amounts are typically already numeric
        if (is_numeric($rawAmount)) {
            return floatval($rawAmount);
        }
        
        // Handle string amounts with currency symbols
        $cleanAmount = $this->cleanAmountString($rawAmount);
        
        if (!is_numeric($cleanAmount)) {
            throw new InvalidArgumentException("Invalid Mercury amount: {$rawAmount}");
        }
        
        return floatval($cleanAmount);
    }

    /**
     * Parse Payoneer amount: EUR format uses strings with commas, USD uses floats
     */
    private function parsePayoneerAmount($rawAmount, ?string $currency = null): float
    {
        // Handle null or empty amounts
        if ($rawAmount === null || $rawAmount === '') {
            return 0.0;
        }
        
        // If it's already a number (USD format), return it
        if (is_numeric($rawAmount)) {
            return floatval($rawAmount);
        }
        
        // Handle string format (typically EUR with commas)
        if (is_string($rawAmount)) {
            return $this->parsePayoneerStringAmount($rawAmount);
        }
        
        throw new InvalidArgumentException("Invalid Payoneer amount: {$rawAmount}");
    }

    /**
     * Parse Payoneer string amount format: "1,234.56" → 1234.56
     */
    private function parsePayoneerStringAmount(string $rawAmount): float
    {
        $rawAmount = trim($rawAmount);
        
        // Remove common currency symbols and spaces
        $cleanAmount = preg_replace('/[€£$¥₹\s]/', '', $rawAmount);
        
        // Handle European format with commas as thousand separators
        // "1,234.56" → 1234.56
        if (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d{2})?$/', $cleanAmount)) {
            return floatval(str_replace(',', '', $cleanAmount));
        }
        
        // Handle simple decimal: "1234.56"
        if (preg_match('/^-?\d+(\.\d+)?$/', $cleanAmount)) {
            return floatval($cleanAmount);
        }
        
        // Handle European decimal format: "1234,56" (comma as decimal separator)
        if (preg_match('/^-?\d+,\d{2}$/', $cleanAmount)) {
            return floatval(str_replace(',', '.', $cleanAmount));
        }
        
        // Handle amounts with only commas: "1,234"
        if (preg_match('/^-?\d{1,3}(,\d{3})*$/', $cleanAmount)) {
            return floatval(str_replace(',', '', $cleanAmount));
        }
        
        throw new InvalidArgumentException("Unable to parse Payoneer amount: {$rawAmount}");
    }

    /**
     * Parse Stripe amount: standard float values
     */
    private function parseStripeAmount($rawAmount): float
    {
        if (is_numeric($rawAmount)) {
            return floatval($rawAmount);
        }
        
        // Handle string amounts
        if (is_string($rawAmount)) {
            $cleanAmount = $this->cleanAmountString($rawAmount);
            
            if (!is_numeric($cleanAmount)) {
                throw new InvalidArgumentException("Invalid Stripe amount: {$rawAmount}");
            }
            
            return floatval($cleanAmount);
        }
        
        throw new InvalidArgumentException("Invalid Stripe amount: {$rawAmount}");
    }

    /**
     * Clean amount string by removing currency symbols and formatting
     */
    private function cleanAmountString(string $amount): string
    {
        $amount = trim($amount);
        
        // Remove common currency symbols
        $amount = preg_replace('/[$€£¥₹]/', '', $amount);
        
        // Remove spaces
        $amount = preg_replace('/\s+/', '', $amount);
        
        // Handle parentheses as negative (accounting format)
        if (preg_match('/^\((.*)\)$/', $amount, $matches)) {
            $amount = '-' . $matches[1];
        }
        
        return $amount;
    }

    /**
     * Detect currency from amount string
     */
    public function detectCurrency(string $rawAmount): ?string
    {
        $currencySymbols = [
            '$' => 'USD',
            '€' => 'EUR', 
            '£' => 'GBP',
            '¥' => 'JPY',
            '₹' => 'INR'
        ];
        
        foreach ($currencySymbols as $symbol => $currency) {
            if (str_contains($rawAmount, $symbol)) {
                return $currency;
            }
        }
        
        return null;
    }

    /**
     * Validate amount is within reasonable range
     */
    public function validateAmount(float $amount): bool
    {
        // Check for reasonable transaction limits
        $maxAmount = 1000000.00; // 1 million
        $minAmount = -1000000.00; // -1 million (refunds)
        
        return $amount >= $minAmount && $amount <= $maxAmount;
    }

    /**
     * Format amount for display
     */
    public function formatForDisplay(float $amount, string $currency = 'USD'): string
    {
        $formatted = number_format(abs($amount), 2);
        
        $currencySymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'INR' => '₹'
        ];
        
        $symbol = $currencySymbols[$currency] ?? $currency;
        
        if ($amount < 0) {
            return "-{$symbol}{$formatted}";
        }
        
        return "{$symbol}{$formatted}";
    }

    /**
     * Convert amount to cents (for database storage)
     */
    public function toCents(float $amount): int
    {
        return intval(round($amount * 100));
    }

    /**
     * Convert cents to amount
     */
    public function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Parse multiple amounts from Stripe transactions
     */
    public function parseStripeMultipleAmounts(array $record): array
    {
        $amounts = [];
        
        // Parse Amount, Fee, Net for Stripe Balance
        if (isset($record['Amount'])) {
            $amounts['amount'] = $this->parseStripeAmount($record['Amount']);
        }
        
        if (isset($record['Fee'])) {
            $amounts['fee'] = $this->parseStripeAmount($record['Fee']);
        }
        
        if (isset($record['Net'])) {
            $amounts['net'] = $this->parseStripeAmount($record['Net']);
        }
        
        // Validate Net = Amount - Fee (with small tolerance for rounding)
        if (isset($amounts['amount'], $amounts['fee'], $amounts['net'])) {
            $calculatedNet = $amounts['amount'] - $amounts['fee'];
            $tolerance = 0.01; // 1 cent tolerance
            
            if (abs($calculatedNet - $amounts['net']) > $tolerance) {
                throw new InvalidArgumentException(
                    "Stripe amount calculation error: Amount({$amounts['amount']}) - Fee({$amounts['fee']}) ≠ Net({$amounts['net']})"
                );
            }
        }
        
        return $amounts;
    }

    /**
     * Get amount parsing examples for documentation
     */
    public function getParsingExamples(string $format): array
    {
        return match($format) {
            BankFormatDetector::FORMAT_MERCURY => [
                '1234.56' => 1234.56,
                '-500.75' => -500.75,
                '$1,000.00' => 1000.00,
                '(250.00)' => -250.00
            ],
            BankFormatDetector::FORMAT_PAYONEER => [
                '"1,234.56"' => 1234.56,
                '10000.00' => 10000.00,
                '"500,000.75"' => 500000.75,
                '0.01' => 0.01
            ],
            BankFormatDetector::FORMAT_STRIPE_BALANCE,
            BankFormatDetector::FORMAT_STRIPE_PAYMENTS => [
                '29.99' => 29.99,
                '1.17' => 1.17,
                '28.82' => 28.82,
                '0.00' => 0.00
            ],
            default => []
        };
    }
} 