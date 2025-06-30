<?php

namespace App\Services\Import\Detectors;

use InvalidArgumentException;

class BankFormatDetector
{
    // Supported CSV formats
    public const FORMAT_MERCURY = 'mercury';

    public const FORMAT_PAYONEER = 'payoneer';

    public const FORMAT_STRIPE_BALANCE = 'stripe_balance';

    public const FORMAT_STRIPE_PAYMENTS = 'stripe_payments';

    public const FORMAT_UNKNOWN = 'unknown';

    /**
     * Detect CSV format based on column headers
     */
    public function detectFormat(array $headers): string
    {
        // Normalize headers for comparison (lowercase, trim)
        $normalizedHeaders = array_map(fn ($header) => strtolower(trim($header)), $headers);
        $headerString = implode('|', $normalizedHeaders);

        // Mercury Bank Detection - Look for "Date (UTC)" + "Source Account"
        if ($this->isMercuryFormat($normalizedHeaders)) {
            return self::FORMAT_MERCURY;
        }

        // Payoneer Detection - Look for "Running Balance" + "Currency"
        if ($this->isPayoneerFormat($normalizedHeaders)) {
            return self::FORMAT_PAYONEER;
        }

        // Stripe Balance Detection - Look for "Fee" + "Net" + "shop_name (metadata)"
        if ($this->isStripeBalanceFormat($normalizedHeaders)) {
            return self::FORMAT_STRIPE_BALANCE;
        }

        // Stripe Payments Detection - Look for "Amount Refunded" + "Converted Currency"
        if ($this->isStripePaymentsFormat($normalizedHeaders)) {
            return self::FORMAT_STRIPE_PAYMENTS;
        }

        return self::FORMAT_UNKNOWN;
    }

    /**
     * Get column mappings for the detected format
     */
    public function getColumnMapping(string $format): array
    {
        return match ($format) {
            self::FORMAT_MERCURY => $this->getMercuryMapping(),
            self::FORMAT_PAYONEER => $this->getPayoneerMapping(),
            self::FORMAT_STRIPE_BALANCE => $this->getStripeBalanceMapping(),
            self::FORMAT_STRIPE_PAYMENTS => $this->getStripePaymentsMapping(),
            default => throw new InvalidArgumentException("Unknown format: {$format}")
        };
    }

    /**
     * Get required columns for format validation
     */
    public function getRequiredColumns(string $format): array
    {
        return match ($format) {
            self::FORMAT_MERCURY => ['date', 'description', 'amount', 'status'],
            self::FORMAT_PAYONEER => ['date', 'description', 'amount', 'currency', 'status'],
            self::FORMAT_STRIPE_BALANCE => ['id', 'type', 'amount', 'fee', 'net', 'currency'],
            self::FORMAT_STRIPE_PAYMENTS => ['id', 'amount', 'currency', 'status', 'created_date'],
            default => []
        };
    }

    /**
     * Validate that all required columns are present
     */
    public function validateFormat(array $headers, string $format): array
    {
        $mapping = $this->getColumnMapping($format);
        $required = $this->getRequiredColumns($format);
        $errors = [];

        foreach ($required as $field) {
            if (! isset($mapping[$field]) || $mapping[$field] === null) {
                $errors[] = "Required field '{$field}' not found in CSV headers";
            }
        }

        return $errors;
    }

    /**
     * Get format detection confidence score
     */
    public function getDetectionConfidence(array $headers, string $format): float
    {
        $normalizedHeaders = array_map(fn ($header) => strtolower(trim($header)), $headers);

        return match ($format) {
            self::FORMAT_MERCURY => $this->getMercuryConfidence($normalizedHeaders),
            self::FORMAT_PAYONEER => $this->getPayoneerConfidence($normalizedHeaders),
            self::FORMAT_STRIPE_BALANCE => $this->getStripeBalanceConfidence($normalizedHeaders),
            self::FORMAT_STRIPE_PAYMENTS => $this->getStripePaymentsConfidence($normalizedHeaders),
            default => 0.0
        };
    }

    /**
     * Mercury Bank format detection
     */
    private function isMercuryFormat(array $headers): bool
    {
        $hasDateUtc = $this->hasHeaderContaining($headers, ['date (utc)', 'date(utc)']);
        $hasSourceAccount = $this->hasHeaderContaining($headers, ['source account']);
        $hasBankDescription = $this->hasHeaderContaining($headers, ['bank description']);

        return $hasDateUtc && $hasSourceAccount && $hasBankDescription;
    }

    private function getMercuryConfidence(array $headers): float
    {
        $score = 0;
        $maxScore = 5;

        if ($this->hasHeaderContaining($headers, ['date (utc)', 'date(utc)'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['source account'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['bank description'])) {
            $score += 1;
        }

        return round($score / $maxScore, 2);
    }

    /**
     * Payoneer format detection
     */
    private function isPayoneerFormat(array $headers): bool
    {
        $hasRunningBalance = $this->hasHeaderContaining($headers, ['running balance']);
        $hasCurrency = $this->hasHeaderContaining($headers, ['currency']);
        $hasTransactionId = $this->hasHeaderContaining($headers, ['transaction id']);

        return $hasRunningBalance && $hasCurrency && $hasTransactionId;
    }

    private function getPayoneerConfidence(array $headers): float
    {
        $score = 0;
        $maxScore = 4;

        if ($this->hasHeaderContaining($headers, ['running balance'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['currency'])) {
            $score += 1;
        }
        if ($this->hasHeaderContaining($headers, ['transaction id'])) {
            $score += 1;
        }

        return round($score / $maxScore, 2);
    }

    /**
     * Stripe Balance format detection
     */
    private function isStripeBalanceFormat(array $headers): bool
    {
        $hasFee = $this->hasHeaderContaining($headers, ['fee']);
        $hasNet = $this->hasHeaderContaining($headers, ['net']);
        $hasShopMetadata = $this->hasHeaderContaining($headers, ['shop_name (metadata)', 'shop_name(metadata)']);

        return $hasFee && $hasNet && $hasShopMetadata;
    }

    private function getStripeBalanceConfidence(array $headers): float
    {
        $score = 0;
        $maxScore = 5;

        if ($this->hasHeaderContaining($headers, ['fee'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['net'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['shop_name (metadata)', 'shop_name(metadata)'])) {
            $score += 1;
        }

        return round($score / $maxScore, 2);
    }

    /**
     * Stripe Payments format detection
     */
    private function isStripePaymentsFormat(array $headers): bool
    {
        $hasAmountRefunded = $this->hasHeaderContaining($headers, ['amount refunded']);
        $hasConvertedCurrency = $this->hasHeaderContaining($headers, ['converted currency']);
        $hasCustomerEmail = $this->hasHeaderContaining($headers, ['customer email']);

        return $hasAmountRefunded && $hasConvertedCurrency;
    }

    private function getStripePaymentsConfidence(array $headers): float
    {
        $score = 0;
        $maxScore = 6;

        if ($this->hasHeaderContaining($headers, ['amount refunded'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['converted currency'])) {
            $score += 2;
        }
        if ($this->hasHeaderContaining($headers, ['customer email'])) {
            $score += 1;
        }
        if ($this->hasHeaderContaining($headers, ['statement descriptor'])) {
            $score += 1;
        }

        return round($score / $maxScore, 2);
    }

    /**
     * Helper method to check if headers contain specific terms
     */
    private function hasHeaderContaining(array $headers, array $searchTerms): bool
    {
        foreach ($headers as $header) {
            foreach ($searchTerms as $term) {
                if (str_contains($header, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mercury Bank column mappings
     */
    private function getMercuryMapping(): array
    {
        return [
            'date' => 'Date (UTC)',
            'description' => 'Description',
            'amount' => 'Amount',
            'status' => 'Status',
            'source_account' => 'Source Account',
            'bank_description' => 'Bank Description',
            'reference' => 'Reference',
            'note' => 'Note',
            'last_four_digits' => 'Last Four Digits',
            'name_on_card' => 'Name On Card',
            'mercury_category' => 'Mercury Category',
            'category' => 'Category',
            'gl_code' => 'GL Code',
            'timestamp' => 'Timestamp',
            'original_currency' => 'Original Currency',
            'check_number' => 'Check Number',
            'tags' => 'Tags',
        ];
    }

    /**
     * Payoneer column mappings
     */
    private function getPayoneerMapping(): array
    {
        return [
            'date' => 'Date',
            'description' => 'Description',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'status' => 'Status',
            'running_balance' => 'Running Balance',
            'transaction_id' => 'Transaction ID',
        ];
    }

    /**
     * Stripe Balance column mappings
     */
    private function getStripeBalanceMapping(): array
    {
        return [
            'id' => 'id',
            'type' => 'Type',
            'source' => 'Source',
            'amount' => 'Amount',
            'fee' => 'Fee',
            'net' => 'Net',
            'currency' => 'Currency',
            'created_date' => 'Created (UTC)',
            'available_on' => 'Available On (UTC)',
            'refund_id' => 'payments_refund_id (metadata)',
            'shop_name' => 'shop_name (metadata)',
            'shop_id' => 'shop_id (metadata)',
            'email' => 'email (metadata)',
            'order_id' => 'order_id (metadata)',
            'manual_entry' => 'manual_entry (metadata)',
        ];
    }

    /**
     * Stripe Payments column mappings
     */
    private function getStripePaymentsMapping(): array
    {
        return [
            'id' => 'id',
            'created_date' => 'Created date (UTC)',
            'amount' => 'Amount',
            'amount_refunded' => 'Amount Refunded',
            'currency' => 'Currency',
            'captured' => 'Captured',
            'converted_amount' => 'Converted Amount',
            'converted_amount_refunded' => 'Converted Amount Refunded',
            'converted_currency' => 'Converted Currency',
            'decline_reason' => 'Decline Reason',
            'description' => 'Description',
            'fee' => 'Fee',
            'refunded_date' => 'Refunded date (UTC)',
            'statement_descriptor' => 'Statement Descriptor',
            'status' => 'Status',
            'seller_message' => 'Seller Message',
            'taxes_on_fee' => 'Taxes On Fee',
            'card_id' => 'Card ID',
            'customer_id' => 'Customer ID',
            'customer_description' => 'Customer Description',
            'customer_email' => 'Customer Email',
            'invoice_id' => 'Invoice ID',
            'transfer' => 'Transfer',
            'shop_name' => 'shop_name (metadata)',
            'shop_id' => 'shop_id (metadata)',
            'email' => 'email (metadata)',
            'order_id' => 'order_id (metadata)',
            'manual_entry' => 'manual_entry (metadata)',
        ];
    }

    /**
     * Get all supported formats
     */
    public function getSupportedFormats(): array
    {
        return [
            self::FORMAT_MERCURY => 'Mercury Bank',
            self::FORMAT_PAYONEER => 'Payoneer',
            self::FORMAT_STRIPE_BALANCE => 'Stripe Balance History',
            self::FORMAT_STRIPE_PAYMENTS => 'Stripe Payments Report',
        ];
    }

    /**
     * Get format display name
     */
    public function getFormatDisplayName(string $format): string
    {
        return $this->getSupportedFormats()[$format] ?? 'Unknown Format';
    }

    /**
     * Check if format supports multi-transaction generation (like Stripe)
     */
    public function supportsMultiTransaction(string $format): bool
    {
        return in_array($format, [
            self::FORMAT_STRIPE_BALANCE,
            self::FORMAT_STRIPE_PAYMENTS,
        ]);
    }
}
