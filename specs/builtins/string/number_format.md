# Builtin Contract â€” `number_format`
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
- Accepted argument types for `$num`: `int_t`, `float_t`, and `mixed_t` values that carry numeric scalars already supported by the runtime

## Behavior
- Formats the numeric input with fixed decimal digits and 3-digit thousands grouping.
- Default decimal separator is `.`.
- Default thousands separator is `,`.
- Trailing zeroes are preserved to the requested decimal count.
- Negative `decimals` follow modern PHP-style left-of-decimal rounding.
- Direct `string_t` input is rejected with `TypeError`.
- `mixed_t` values that carry `string_t` are rejected with the same `TypeError`.

## Compatibility table
- PHP supports 1-, 2-, and 4-argument forms â†’ Prism++ supports the same practical forms â†’ kept
- PHP keeps fixed trailing zeroes â†’ Prism++ keeps fixed trailing zeroes â†’ kept
- PHP 8.3+ negative `decimals` round digits before the decimal point â†’ Prism++ now mirrors that practical behavior â†’ kept
- PHP rejects direct string input for `$num` in current versions â†’ Prism++ now rejects direct `string_t` and mixed-string input with `TypeError` â†’ kept

## Error policy
- Does not throw for ordinary formatting inputs.
- Invalid direct `string_t` input throws `TypeError`.
- `mixed_t` values carrying `string_t` also throw `TypeError`.

## Runtime and wrapper split
- Runtime: normalize decimals, round, render fixed precision, and inject grouping separators.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- integer default formatting
- float formatting with decimals
- custom decimal and thousands separators
- negative input
- trailing zero preservation
- negative decimals rounding before the decimal point
- direct string input rejected with `TypeError`
- mixed-string input rejected with `TypeError`
