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
- Return contract: `result_or_false<int_t>`

## Behavior
- Searches for the first occurrence of `needle` in `haystack`.
- Returns absolute zero-based byte position on success.
- Returns `false` when not found.
- Offset is normalized with PHP-like positive/negative semantics.

## Compatibility table
- PHP returns `int|false` and preserves `0` vs `false` â†’ Prism++ preserves it through `result_or_false<int_t>` â†’ kept
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
- Shared plain-name exposure in strict should preserve the same visible `int|false` contract through the PHP adapter layer rather than exposing the internal strict nullable search helper directly.
- The visible PHP-facing carrier for this shared helper contract is `result_or_false<int_t>`, not `mixed_t` and not `nullable<int_t>`.

## Test matrix
- found at position zero
- found after positive offset
- found after negative offset
- not found returns `false`
- out-of-range offset throws

## v0.2 source admission — 2026-10-20

The strings module exposes exactly the two forms above through value parameters
and `result_or_false<int>`. Positions and offsets count bytes. Arbitrary bytes,
including embedded NUL and malformed UTF-8, are accepted without UTF-8 decoding.
An empty needle matches a byte boundary. Offset bounds are inclusive
`[-byte_size, byte_size]`; out-of-range offsets throw `ValueError` even when the
needle is empty or absent. Negative normalization must safely handle the full
signed integer domain. Position zero remains a successful payload, never false.

Argument expressions evaluate once, left to right. Earlier argument values are
snapshotted before later expressions can mutate their source storage. Inputs are
not mutated, returned positions are independent values, and argument evaluation
errors propagate without evaluating subsequent arguments. No implicit wrapper
extraction or flow-sensitive narrowing is introduced by these bindings.

The v0.2 provider target is `scpp::php::strpos_bytes` in
`runtime/include/lang/php/support/php_string.hpp`. The historical
`scpp::php::strpos` adapter retains its code-point behavior for existing generated
consumers and the portability text framework. That adapter's behavior does not
override this source contract. Shared code-point search and the explicit
`string_byte_find` nonnegative-offset contract remain unchanged.
