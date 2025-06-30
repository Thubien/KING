<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkrainianIbanRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Let required rule handle empty values
        }

        // Ukrainian IBAN format: UA + 2 check digits + 6-digit MFO + 19-digit account number = 29 characters total
        if (strlen($value) !== 29) {
            $fail('Ukrainian IBAN must be exactly 29 characters long.');

            return;
        }

        // Must start with UA
        if (substr($value, 0, 2) !== 'UA') {
            $fail('Ukrainian IBAN must start with "UA".');

            return;
        }

        // Check digits (positions 2-3) must be numeric
        $checkDigits = substr($value, 2, 2);
        if (! ctype_digit($checkDigits)) {
            $fail('Invalid check digits in Ukrainian IBAN.');

            return;
        }

        // MFO code (positions 4-9) must be numeric
        $mfoCode = substr($value, 4, 6);
        if (! ctype_digit($mfoCode)) {
            $fail('Invalid MFO code in Ukrainian IBAN.');

            return;
        }

        // Account number (positions 10-28) must be numeric
        $accountNumber = substr($value, 10, 19);
        if (! ctype_digit($accountNumber)) {
            $fail('Invalid account number in Ukrainian IBAN.');

            return;
        }

        // Basic IBAN checksum validation (mod 97)
        if (! $this->validateIbanChecksum($value)) {
            $fail('Invalid Ukrainian IBAN checksum.');

            return;
        }
    }

    /**
     * Validate IBAN checksum using mod 97 algorithm
     */
    private function validateIbanChecksum(string $iban): bool
    {
        // Move first 4 characters to end
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord(strtoupper($char)) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod 97
        $remainder = 0;
        for ($i = 0; $i < strlen($numeric); $i++) {
            $remainder = ($remainder * 10 + intval($numeric[$i])) % 97;
        }

        return $remainder === 1;
    }
}
