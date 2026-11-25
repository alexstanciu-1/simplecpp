# Builtin Contract — `number_format`
Doc Status: normative
## Identity
- Name: `number_format`
- Module/family: string
- Category/classification: Numeric Formatting
- Status: experimental
- Source-language reference target: PHP `number_format`
- Compatibility level: practical

## Signature
- Supported forms:
  - `number_format($num): string`
  - `number_format($num, int $decimals): string`
  - `number_format($num, int $decimals, string $decimal_separator, string $thousands_separator): string`
- v0.2 accepted argument types for `$num`: canonical `int` and `float`, using all
  three forms above. Bool, string, mixed and dynamic inputs reject during preparation.
- Historical runtime/S2S mixed overloads are not enabled in the v0.2 catalog.
  Boxed formatting is deferred until numeric-kind checks are explicit; unsupported
  table kinds must not silently format as zero. See CH15-RUNTIME-FORMAT-01.

## Behavior
- Formats the numeric input with fixed decimal digits and 3-digit thousands grouping.
- Default decimal separator is `.`.
- Default thousands separator is `,`.
- Integers retain their full int64 precision. Decimal ties round away from zero, including a carry past the original int64 range. Rounded zero has no minus sign.
- Separators are literal byte strings and may be empty or contain NUL. Group after rounding.
- Trailing zeroes are preserved to the requested decimal count.
- Negative `decimals` follow modern PHP-style left-of-decimal rounding.
- Direct string input rejects during v0.2 preparation. Historical runtime overloads
  throw `TypeError` for direct strings and mixed strings.

## PHP compatibility
- The PHP flavor exposes the one-, two-, and four-argument forms.
- Fixed precision preserves fractional trailing zeroes. Negative precision rounds before the decimal point.
- PHP can coerce numeric strings in weak mode. Simple C++ requires an exact numeric first argument during preparation.

## Error policy
- A finite result whose output length cannot be represented raises `length_error`. Nonfinite float inputs use PHP’s `nan` and `inf` spellings (both infinity signs format as `inf`).
- Statically unsupported input types/arity reject during v0.2 preparation.
- Historical runtime string/mixed-string overloads throw `TypeError`; this does
  not imply availability of boxed formatting in the v0.2 catalog.

## Runtime and wrapper split
- Runtime: normalize decimals, round, render fixed precision, and inject grouping separators.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented by shared string runtime support under `runtime/include/modules/strings/strings.hpp` with PHP wrapper exposure in `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.
- The active v0.2 [runtime catalog](../../../v0.2/runtime/metadata/runtime.json) publishes the PHP adapter for both numeric inputs and all three arities.

## Test matrix
- integer default formatting
- float formatting with decimals
- custom decimal and thousands separators
- negative input
- trailing zero preservation
- negative decimals rounding before the decimal point
- direct strings and boxed inputs rejected during v0.2 preparation
- embedded NUL and empty separators
- int64 extrema, positive precision overflow, negative precision extrema and rounded zero
