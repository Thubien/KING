<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkrainianMfoRule implements ValidationRule
{
    /**
     * Known Ukrainian bank MFO codes for validation
     */
    private const KNOWN_MFO_CODES = [
        '305299' => 'PrivatBank',
        '300012' => 'Oschadbank',
        '380805' => 'Raiffeisen Bank Aval',
        '254751' => 'PUMB (First Ukrainian International Bank)',
        '351005' => 'UkrSibbank',
        '320984' => 'Alfa-Bank Ukraine',
        '325365' => 'Crédit Agricole Ukraine',
        '300023' => 'Ukrgazbank',
        '325213' => 'Universal Bank',
        '321723' => 'OTP Bank',
        '325889' => 'FUIB (First Ukrainian International Bank)',
        '300711' => 'Ukrsibbank BNP Paribas Group',
        '326256' => 'PRAVEX BANK',
        '325990' => 'Tascombank',
        '300528' => 'Ukreximbank'
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Let required rule handle empty values
        }

        // MFO code must be exactly 6 digits
        if (!preg_match('/^\d{6}$/', $value)) {
            $fail('Ukrainian MFO code must be exactly 6 digits.');
            return;
        }

        // Check if it's a known MFO code (optional validation)
        if (isset(self::KNOWN_MFO_CODES[$value])) {
            // Valid known MFO code
            return;
        }

        // For unknown MFO codes, we can still allow them but with a warning
        // This is because new banks or branches might have MFO codes not in our list
        
        // Basic validation: MFO codes in Ukraine typically start with 3
        if (!str_starts_with($value, '3')) {
            $fail('Ukrainian MFO codes typically start with "3". Please verify this code.');
            return;
        }

        // Additional validation could be added here for:
        // - Regional validation (certain ranges for certain regions)
        // - Bank type validation (state vs private banks)
        // - Active bank validation (checking against NBU database)
    }

    /**
     * Get bank name for a given MFO code
     */
    public static function getBankName(string $mfoCode): ?string
    {
        return self::KNOWN_MFO_CODES[$mfoCode] ?? null;
    }

    /**
     * Get all known MFO codes
     */
    public static function getKnownMfoCodes(): array
    {
        return self::KNOWN_MFO_CODES;
    }
}