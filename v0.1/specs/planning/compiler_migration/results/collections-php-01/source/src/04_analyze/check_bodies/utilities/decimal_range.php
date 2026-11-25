<?php
declare(strict_types=1);

/* Exact positive decimal range checking; definition validation stays in Integer_Literals. */
namespace check_bodies;
// <scpp-imports>
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

class Decimal_Range
{
    /** Normalized nonzero ASCII decimal input; caller owns validation and zero handling. */
    public static function fits_positive(string $value, int $bits): bool
    {
        // Reject huge source literals before repeated division, without parsing a host integer.
        if (string_byte_len($value) > $bits) {
            return false;
        }
        $remaining = $value;
        for ($used = 0; $remaining !== ''; ++$used)
        {
            if ($used >= $bits) {
                return false;
            }
            $quotient = '';
            $carry = 0;
            $length = string_byte_len($remaining);
            for ($i = 0; $i < $length; ++$i)
            {
                $digit = ($carry * 10) + string_byte_at($remaining, $i) - 48;
                // The normalized digit/carry domain is 0..19; this division is exact before truncation.
                $q = (int)($digit / 2);
                if (($q !== 0) || ($quotient !== '')) {
                    $quotient .= string_byte_slice('0123456789', $q, 1);
                }
                $carry = $digit % 2;
            }
            $remaining = $quotient;
        }
        return true;
    }
}
