# Builtin Contract â€” `strpos`
Doc Status: normative
## Identity
- Name: `strpos`
- Module/family: string
- Category/classification: Core Utility Wrapper
- Status: experimental
- Source-language reference target: PHP `strpos`
- Compatibility level: practical

## Signature
- Supported forms:
  - `strpos(string $haystack, string $needle): int|false`
  - `strpos(string $haystack, string $needle, int $offset): int|false`
- Accepted argument types: `string_t`, `string_t`, optional `int_t`
- Return contract: `mixed_t` carrying `int_t` or `bool_t(false)`

## Behavior
- Searches for the first occurrence of `needle` in `haystack`.
- Returns absolute zero-based byte position on success.
- Returns `false` when not found.
- Offset is normalized with PHP-like positive/negative semantics.

## Compatibility table
- PHP returns `int|false` and preserves `0` vs `false` â†’ Prism++ preserves it through `mixed_t` â†’ kept
- PHP offset out of haystack range raises `ValueError` â†’ Prism++ raises `ValueError` â†’ kept
- PHP is byte-oriented here â†’ Prism++ is byte-oriented â†’ kept

## Error policy
- Throws `ValueError` for out-of-range offsets.

## Runtime and wrapper split
- Runtime: offset normalization, search, sentinel shaping.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented by shared string runtime support under `runtime/include/modules/strings/strings.hpp` with PHP wrapper exposure in `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- found at position zero
- found after positive offset
- found after negative offset
- not found returns `false`
- out-of-range offset throws
