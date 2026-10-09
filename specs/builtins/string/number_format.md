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
- v0.2 accepted argument types for `$num`: canonical `int` and `float`, using all
  three forms above. Bool, string, mixed and dynamic inputs reject during preparation.
- Historical runtime/S2S mixed overloads are not enabled in the v0.2 catalog.
  Boxed formatting is deferred until numeric-kind checks are explicit; unsupported
  table kinds must not silently format as zero. See CH15-RUNTIME-FORMAT-01.

## Behavior
- Formats the numeric input with fixed decimal digits and 3-digit thousands grouping.
- Default decimal separator is `.`.
- Default thousands separator is `,`.
- Trailing zeroes are preserved to the requested decimal count.
- Negative `decimals` follow modern PHP-style left-of-decimal rounding.
- Direct string input rejects during v0.2 preparation. Historical runtime overloads
  throw `TypeError` for direct strings and mixed strings.

## Compatibility table
- PHP supports 1-, 2-, and 4-argument forms â†’ Prism++ supports the same practical forms â†’ kept
- PHP keeps fixed trailing zeroes â†’ Prism++ keeps fixed trailing zeroes â†’ kept
- PHP 8.3+ negative `decimals` round digits before the decimal point â†’ Prism++ now mirrors that practical behavior â†’ kept
- PHP rejects direct string input for `$num` in current versions â†’ Prism++ now rejects direct `string_t` and mixed-string input with `TypeError` â†’ kept

## Error policy
- Does not throw for ordinary formatting inputs.
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
- v0.2 int/float contracts are registered in `runtime/metadata/modules/strings.json`.

## Test matrix
- integer default formatting
- float formatting with decimals
- custom decimal and thousands separators
- negative input
- trailing zero preservation
- negative decimals rounding before the decimal point
- direct string input rejected with `TypeError`
- mixed-string input rejected with `TypeError`
