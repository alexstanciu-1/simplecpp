<?php
declare(strict_types=1);
namespace type_model;
const ABI_INTEGER = 1;
const ABI_BORROW = 2;
const ABI_BYTE_SPAN = 3;
const ABI_EXTENSION_NONE = 0;
const ABI_EXTENSION_SIGN = 1;
const ABI_EXTENSION_ZERO = 2;
const ABI_RESULT_DIRECT = 0;
const ABI_RESULT_CALLER_STORAGE = 1;
const BINDING_BYTE_LITERAL = 0;
const BINDING_ECHO = 1;
const CONVERSION_IMPLICIT = 0;
const CONVERSION_EXPLICIT = 1;
const CONVERSION_CONDITION = 2;
const CONVERSION_TEXT = 3;

/** Provider vocabulary stays distinct from implementation names and semantic passing. */
final class Callable_Modes {
    public static function extension_name(int $value): string {
        $name = '';
        if ($value === \type_model\ABI_EXTENSION_NONE) { $name = ''; }
        elseif ($value === \type_model\ABI_EXTENSION_SIGN) { $name = 'signext'; }
        elseif ($value === \type_model\ABI_EXTENSION_ZERO) { $name = 'zeroext'; }
        else { throw new \InvalidArgumentException('Unknown callable extension'); }
        return $name;
    }
    public static function extension(string $name): int {
        $value = 0;
        if ($name === '') { $value = \type_model\ABI_EXTENSION_NONE; }
        elseif ($name === 'signext') { $value = \type_model\ABI_EXTENSION_SIGN; }
        elseif ($name === 'zeroext') { $value = \type_model\ABI_EXTENSION_ZERO; }
        else { throw new \InvalidArgumentException('Unknown callable extension'); }
        return $value;
    }
    public static function result_name(int $value): string {
        $name = '';
        if ($value === \type_model\ABI_RESULT_DIRECT) { $name = 'direct'; }
        elseif ($value === \type_model\ABI_RESULT_CALLER_STORAGE) { $name = 'caller_storage'; }
        else { throw new \InvalidArgumentException('Unknown callable result'); }
        return $name;
    }
    public static function result(string $name): int {
        $value = 0;
        if ($name === 'direct') { $value = \type_model\ABI_RESULT_DIRECT; }
        elseif ($name === 'caller_storage') { $value = \type_model\ABI_RESULT_CALLER_STORAGE; }
        else { throw new \InvalidArgumentException('Unknown callable result'); }
        return $value;
    }
    public static function binding_name(int $value): string {
        $name = '';
        if ($value === \type_model\BINDING_BYTE_LITERAL) { $name = 'byte_literal'; }
        elseif ($value === \type_model\BINDING_ECHO) { $name = 'echo'; }
        else { throw new \InvalidArgumentException('Unknown callable binding'); }
        return $name;
    }
    public static function binding(string $name): int {
        $value = 0;
        if ($name === 'byte_literal') { $value = \type_model\BINDING_BYTE_LITERAL; }
        elseif ($name === 'echo') { $value = \type_model\BINDING_ECHO; }
        else { throw new \InvalidArgumentException('Unknown callable binding'); }
        return $value;
    }
    public static function conversion_name(int $value): string {
        $name = '';
        if ($value === \type_model\CONVERSION_IMPLICIT) { $name = 'implicit'; }
        elseif ($value === \type_model\CONVERSION_EXPLICIT) { $name = 'explicit_cast'; }
        elseif ($value === \type_model\CONVERSION_CONDITION) { $name = 'condition'; }
        elseif ($value === \type_model\CONVERSION_TEXT) { $name = 'text'; }
        else { throw new \InvalidArgumentException('Unknown callable conversion'); }
        return $name;
    }
    public static function conversion(string $name): int {
        $value = 0;
        if ($name === 'implicit') { $value = \type_model\CONVERSION_IMPLICIT; }
        elseif ($name === 'explicit_cast') { $value = \type_model\CONVERSION_EXPLICIT; }
        elseif ($name === 'condition') { $value = \type_model\CONVERSION_CONDITION; }
        elseif ($name === 'text') { $value = \type_model\CONVERSION_TEXT; }
        else { throw new \InvalidArgumentException('Unknown callable conversion'); }
        return $value;
    }
}
