<?php
declare(strict_types=1);

/*
 * Role: Validate integer literal values against their definition.
 * Used by: Body_Worker expression handlers
 * Call map:
 *   Integer_Literals::resolve()
 *     -> [action] validate decimal text and representable range
 */

namespace check_bodies;

// Exact nonnegative decimal literals. Keep values independent of host integer
// width; no PHP overflow-to-float or destination-driven type inference.
/** @compiler-internal Checking-owned exact decimal interpretation helper; not a backend literal policy. */
class Integer_Literals
{
    /**
     * @compiler-internal Normalize decimal digits against the supplied authoritative integer definition.
     * Return exact text; RangeException on overflow and LogicException on invalid inputs.
     */
    public static function resolve(string $digits, \type_model\named_type_definition $definition): string
    {
        if (($definition->representation->kind !== \type_model\representation_kind::integer)
            || (preg_match('/\A[0-9]+\z/', $digits) !== 1)) {
            throw new \LogicException('Expected decimal integer literal and integer definition');
        }
        $value = ltrim($digits, '0');
        if ($value === '') {
            return '0';
        }
        $bits = $definition->representation->payload->bit_width - ($definition->signed ? 1 : 0);

        if (!Decimal_Range::fits_positive($value, $bits)) {
            throw new \RangeException('Integer literal is outside the range of ' . $definition->name);
        }
        return $value;
    }
}
