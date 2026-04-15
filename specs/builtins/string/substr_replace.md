# Builtin Contract — `substr_replace`

## Identity
- Name: `substr_replace`
- Module/family: string
- Category/classification: Offset Replace Wrapper
- Status: experimental
- Source-language reference target: PHP `substr_replace`
- Compatibility level: practical

## Signature
- Supported forms:
  - `substr_replace(string $subject, string $replacement, int $offset): string`
  - `substr_replace(string $subject, string $replacement, int $offset, int $length): string`
- Accepted argument types: `string_t`, `string_t`, `int_t`, optional `int_t`
- Current scope: string subject and string replacement only

## Behavior
- Replaces a normalized window inside `subject` with `replacement`.
- Negative offsets count from the end.
- Omitted length inserts `replacement` at the normalized offset without removing a suffix.
- Negative lengths trim the replacement window from the end of `subject`.
- Positive offsets beyond the end append the replacement.
- Excessively negative offsets normalize to the start.
- If a negative length yields no usable replacement window, Prism++ returns the original string unchanged.

## Compatibility table
- PHP supports negative offset and length normalization → Prism++ keeps the same practical normalization direction → kept
- PHP supports array subject/replacement forms → Prism++ currently supports string-only forms → modified
- PHP edge handling is broader and noisier → Prism++ uses deterministic append/start/no-op behavior → modified

## Error policy
- No runtime error for valid typed inputs.
- Unusable negative-length windows become a no-op and return the original string.

## Runtime and wrapper split
- Runtime: normalize the replacement window and rebuild the output string.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- positive offset replace
- negative offset replace
- append when offset is beyond end
- normalize to start for oversized negative offset
- negative length valid window
- negative length invalid no-op
