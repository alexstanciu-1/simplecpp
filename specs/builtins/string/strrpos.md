# Builtin Contract â€” `strrpos`
Doc Status: normative
## Identity
- Name: `strrpos`
- Module/family: string
- Category/classification: Core Utility Wrapper
- Status: experimental
- Source-language reference target: PHP `strrpos`
- Compatibility level: practical

## Signature
- Supported forms:
  - `strrpos(string $haystack, string $needle): int|false`
  - `strrpos(string $haystack, string $needle, int $offset): int|false`
- Accepted argument types: `string_t`, `string_t`, optional `int_t`
- Return contract: `mixed_t` carrying `int_t` or `bool_t(false)`

## Behavior
- Searches for the last occurrence of `needle` in `haystack`.
- Returns absolute zero-based byte position on success.
- Returns `false` when not found.
- Positive offset limits the search to the suffix starting at that offset.
- Negative offset limits the search to the prefix ending at `size + offset`.

## Compatibility table
- PHP returns `int|false` and preserves `0` vs `false` â†’ Prism++ preserves it through `mixed_t` â†’ kept
- PHP offset out of haystack range raises `ValueError` â†’ Prism++ raises `ValueError` â†’ kept
- PHP is byte-oriented here â†’ Prism++ is byte-oriented â†’ kept

## Error policy
- Throws `scpp::php::ValueError` for out-of-range offsets.

## Runtime and wrapper split
- Runtime: offset normalization, reverse-search windowing, sentinel shaping.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- found without offset
- found with positive offset
- found with negative offset
- not found returns `false`
- out-of-range offset throws
