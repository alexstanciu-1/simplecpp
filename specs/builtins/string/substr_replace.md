# Builtin Contract â€” `substr_replace`
Doc Status: normative
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
- PHP supports negative offset and length normalization â†’ Prism++ keeps the same practical normalization direction â†’ kept
- PHP supports array subject/replacement forms â†’ Prism++ currently supports string-only forms â†’ modified
- PHP edge handling is broader and noisier â†’ Prism++ uses deterministic append/start/no-op behavior â†’ modified

## Error policy
- No runtime error for valid typed inputs.
- Unusable negative-length windows become a no-op and return the original string.

## Runtime and wrapper split
- Runtime: normalize the replacement window and rebuild the output string.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Shared implementation: `runtime/include/modules/strings/strings.hpp`; PHP adapter: `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- positive offset replace
- negative offset replace
- append when offset is beyond end
- normalize to start for oversized negative offset
- negative length valid window
- negative length invalid no-op

## v0.2 source admission — 2026-10-20

The strings module exposes the three- and four-argument forms with ordinary value
parameters and a string value result. Offsets and lengths count bytes; embedded
NUL and malformed UTF-8 are accepted without decoding. There are no array,
boxed-value or nullable-argument overloads in this slice.

Normalize the offset by clamping positive offsets to the byte size and negative
offsets relative to the end, clamped to zero. Omitted length is exactly equivalent
to explicit zero: insert at the normalized offset. A positive length removes at
most the remaining suffix. For negative length, the candidate end is byte size
plus length. Only an end strictly greater than the normalized start defines a
usable removal window; otherwise return the original string unchanged. This no-op
rule takes precedence over append behavior when the offset normalizes to the end.
All normalization must handle signed integer minimum/maximum without overflow.

Argument expressions evaluate once, left to right, with earlier values snapshotted
before later expressions can mutate their source storage. Every argument is still
evaluated for a no-op window; an argument error propagates and prevents subsequent
argument evaluation. The operation does not mutate either input and returns an
independent value, including when both inputs alias or the result is unchanged.

This reconciles the runtime with the published contract. Historical runtime tests
expected suffix replacement for omitted length and insertion for unusable negative
windows; those expectations change explicitly. Existing generated callers of the
shared helper also receive the corrected behavior. Other slicing/search contracts
and implicit wrapper argument conversion are unchanged.
