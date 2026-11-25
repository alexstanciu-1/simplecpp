<?php
declare(strict_types=1);
namespace type_model;

const PASS_VALUE = 0;
const PASS_BORROW_CONST = 1;
const PASS_BORROW_MUTABLE = 2;
const PASS_BYTE_SPAN = 3;
const RESULT_NONE = 0;
const RESULT_VALUE = 1;
const RESULT_OWNED = 2;
const RESULT_DEPENDENT_VALUE = 3;

/** Stable internal tags; explicit codecs preserve the provider vocabulary. */
final class Semantic_Modes {
    public static function passing(string $name): int {
        $out /** int */ = 0;
        if ($name === 'value') { $out = \type_model\PASS_VALUE; }
        else if ($name === 'borrow_const') { $out = \type_model\PASS_BORROW_CONST; }
        else if ($name === 'borrow_mutable') { $out = \type_model\PASS_BORROW_MUTABLE; }
        else if ($name === 'byte_span') { $out = \type_model\PASS_BYTE_SPAN; }
        else { throw new \InvalidArgumentException('Unknown argument passing mode'); }
        return $out;
    }
    public static function passing_name(int $mode): string {
        $out /** string */ = '';
        if ($mode === \type_model\PASS_VALUE) { $out = 'value'; }
        else if ($mode === \type_model\PASS_BORROW_CONST) { $out = 'borrow_const'; }
        else if ($mode === \type_model\PASS_BORROW_MUTABLE) { $out = 'borrow_mutable'; }
        else if ($mode === \type_model\PASS_BYTE_SPAN) { $out = 'byte_span'; }
        else { throw new \InvalidArgumentException('Unknown argument passing mode'); }
        return $out;
    }
    public static function is_borrow(int $mode): bool {
        Semantic_Modes::passing_name($mode);
        return ($mode === \type_model\PASS_BORROW_CONST) || ($mode === \type_model\PASS_BORROW_MUTABLE);
    }
    public static function result(string $name): int {
        $out /** int */ = 0;
        if ($name === 'none') { $out = \type_model\RESULT_NONE; }
        else if ($name === 'value') { $out = \type_model\RESULT_VALUE; }
        else if ($name === 'owned') { $out = \type_model\RESULT_OWNED; }
        else if ($name === 'dependent_value') { $out = \type_model\RESULT_DEPENDENT_VALUE; }
        else { throw new \InvalidArgumentException('Unknown result production mode'); }
        return $out;
    }
    public static function result_name(int $mode): string {
        $out /** string */ = '';
        if ($mode === \type_model\RESULT_NONE) { $out = 'none'; }
        else if ($mode === \type_model\RESULT_VALUE) { $out = 'value'; }
        else if ($mode === \type_model\RESULT_OWNED) { $out = 'owned'; }
        else if ($mode === \type_model\RESULT_DEPENDENT_VALUE) { $out = 'dependent_value'; }
        else { throw new \InvalidArgumentException('Unknown result production mode'); }
        return $out;
    }
}
