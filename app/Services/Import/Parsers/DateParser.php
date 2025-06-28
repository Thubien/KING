<?php

namespace App\Services\Import\Parsers;

use Carbon\Carbon;
use InvalidArgumentException;
use App\Services\Import\Detectors\BankFormatDetector;

class DateParser
{
    /**
     * Parse date based on detected format
     */
    public function parseDate(string $rawDate, string $format): Carbon
    {
        if (empty(trim($rawDate))) {
            throw new InvalidArgumentException('Date cannot be empty');
        }

        try {
            return match($format) {
                BankFormatDetector::FORMAT_MERCURY => $this->parseMercuryDate($rawDate),
                BankFormatDetector::FORMAT_PAYONEER => $this->parsePayoneerDate($rawDate),
                BankFormatDetector::FORMAT_STRIPE_BALANCE => $this->parseStripeDate($rawDate),
                BankFormatDetector::FORMAT_STRIPE_PAYMENTS => $this->parseStripeDate($rawDate),
                default => throw new InvalidArgumentException("Unknown date format: {$format}")
            };
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Failed to parse date '{$rawDate}' for format '{$format}': " . $e->getMessage());
        }
    }

    /**
     * Parse Mercury Bank date format: "2024-12-25 10:30:00"
     */
    private function parseMercuryDate(string $rawDate): Carbon
    {
        $rawDate = trim($rawDate);
        
        // Handle full datetime format: "2024-12-25 10:30:00"
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $rawDate)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $rawDate);
        }
        
        // Handle date only format: "2024-12-25"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            return Carbon::createFromFormat('Y-m-d', $rawDate);
        }
        
        // Try to parse with Carbon's flexible parser as fallback
        return Carbon::parse($rawDate);
    }

    /**
     * Parse Payoneer date format: "Dec 25, 2024"
     */
    private function parsePayoneerDate(string $rawDate): Carbon
    {
        $rawDate = trim($rawDate);
        
        // Handle "Dec 25, 2024" format
        if (preg_match('/^[A-Za-z]{3} \d{1,2}, \d{4}$/', $rawDate)) {
            return Carbon::createFromFormat('M j, Y', $rawDate);
        }
        
        // Handle "December 25, 2024" format (full month name)
        if (preg_match('/^[A-Za-z]+ \d{1,2}, \d{4}$/', $rawDate)) {
            return Carbon::createFromFormat('F j, Y', $rawDate);
        }
        
        // Handle "25/12/2024" format (EU style)
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate)) {
            return Carbon::createFromFormat('d/m/Y', $rawDate);
        }
        
        // Handle "12/25/2024" format (US style)
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate)) {
            // Try US format first, then EU format
            try {
                return Carbon::createFromFormat('m/d/Y', $rawDate);
            } catch (\Exception $e) {
                return Carbon::createFromFormat('d/m/Y', $rawDate);
            }
        }
        
        // Try to parse with Carbon's flexible parser as fallback
        return Carbon::parse($rawDate);
    }

    /**
     * Parse Stripe date format: "2024-12-25"
     */
    private function parseStripeDate(string $rawDate): Carbon
    {
        $rawDate = trim($rawDate);
        
        // Handle standard ISO date format: "2024-12-25"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            return Carbon::createFromFormat('Y-m-d', $rawDate);
        }
        
        // Handle datetime format: "2024-12-25 10:30:00"
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $rawDate)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $rawDate);
        }
        
        // Handle ISO 8601 format: "2024-12-25T10:30:00Z"
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $rawDate)) {
            return Carbon::parse($rawDate);
        }
        
        // Try to parse with Carbon's flexible parser as fallback
        return Carbon::parse($rawDate);
    }

    /**
     * Validate date is within reasonable range
     */
    public function validateDate(Carbon $date): bool
    {
        $now = Carbon::now();
        $minDate = Carbon::now()->subYears(10); // 10 years ago
        $maxDate = Carbon::now()->addYears(1);  // 1 year in future
        
        return $date->between($minDate, $maxDate);
    }

    /**
     * Get supported date formats for a CSV format
     */
    public function getSupportedFormats(string $csvFormat): array
    {
        return match($csvFormat) {
            BankFormatDetector::FORMAT_MERCURY => [
                'Y-m-d H:i:s' => '2024-12-25 10:30:00',
                'Y-m-d' => '2024-12-25'
            ],
            BankFormatDetector::FORMAT_PAYONEER => [
                'M j, Y' => 'Dec 25, 2024',
                'F j, Y' => 'December 25, 2024',
                'd/m/Y' => '25/12/2024',
                'm/d/Y' => '12/25/2024'
            ],
            BankFormatDetector::FORMAT_STRIPE_BALANCE,
            BankFormatDetector::FORMAT_STRIPE_PAYMENTS => [
                'Y-m-d' => '2024-12-25',
                'Y-m-d H:i:s' => '2024-12-25 10:30:00',
                'ISO 8601' => '2024-12-25T10:30:00Z'
            ],
            default => []
        };
    }

    /**
     * Parse date with automatic format detection
     */
    public function parseWithAutoDetection(string $rawDate): Carbon
    {
        $rawDate = trim($rawDate);
        
        // Try common patterns in order of likelihood
        $patterns = [
            '/^\d{4}-\d{2}-\d{2}$/' => 'Y-m-d',
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/' => 'Y-m-d H:i:s',
            '/^[A-Za-z]{3} \d{1,2}, \d{4}$/' => 'M j, Y',
            '/^[A-Za-z]+ \d{1,2}, \d{4}$/' => 'F j, Y',
            '/^\d{1,2}\/\d{1,2}\/\d{4}$/' => 'd/m/Y', // EU format first
        ];
        
        foreach ($patterns as $pattern => $format) {
            if (preg_match($pattern, $rawDate)) {
                try {
                    return Carbon::createFromFormat($format, $rawDate);
                } catch (\Exception $e) {
                    // Continue to next pattern
                }
            }
        }
        
        // Final fallback to Carbon's built-in parser
        return Carbon::parse($rawDate);
    }

    /**
     * Convert date to database format (Y-m-d)
     */
    public function toDatabaseFormat(Carbon $date): string
    {
        return $date->format('Y-m-d');
    }

    /**
     * Convert date to display format
     */
    public function toDisplayFormat(Carbon $date): string
    {
        return $date->format('M j, Y'); // Dec 25, 2024
    }
} 