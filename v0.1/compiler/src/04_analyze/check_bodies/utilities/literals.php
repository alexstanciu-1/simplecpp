<?php
declare(strict_types=1);
namespace check_bodies;
/** Exact nonnegative decimal interpretation, independent of host integer width. */
final class Integer_Literals {
    public static function resolve(string $digits, \type_model\Named_Definition $definition): string {
        if (($definition->representation->kind()!==\type_model\REPRESENTATION_INTEGER) || (string_byte_len($digits)===0)) { throw new \LogicException('Expected decimal integer literal and integer definition'); }
        $first=-1; $length=string_byte_len($digits);
        for ($i=0;$i<$length;$i++) {
            $byte=string_byte_at($digits,$i);
            if (($byte<48) || ($byte>57)) { throw new \LogicException('Expected decimal integer literal and integer definition'); }
            if ($first===-1) { if ($byte!==48) { $first=$i; } }
        }
        if ($first===-1) { return '0'; }
        $value=string_byte_slice($digits,$first,$length-$first);
        $bits=$definition->representation->bit_width();
        if ($definition->signed===true) { $bits=$bits-1; }
        if (!Decimal_Range::fits_positive($value,$bits)) { throw new \RangeException('Integer literal is outside the range of ' . $definition->name); }
        return $value;
    }
}
