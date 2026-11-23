<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Malaysian mobile number such as 012-3456789, 0112345678 or +60123456789.
 */
class MalaysianPhone implements ValidationRule
{
    public const PATTERN = '/^(\+?60|0)1[0-9]-?[0-9]{7,8}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $compact = is_string($value) ? str_replace(' ', '', $value) : '';

        if (preg_match(self::PATTERN, $compact) !== 1) {
            $fail('Enter a Malaysian mobile number, for example 012-3456789.');
        }
    }
}
