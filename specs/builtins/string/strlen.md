# Builtin Contract â€” `strlen`
Doc Status: normative
## Identity
- Name: `strlen`
- Module/family: string
- Category/classification: Core Utility Wrapper
- Status: experimental
- Source-language reference target: PHP `strlen`
- Compatibility level: practical

## Signature
- Supported form: `strlen(string $value): int`
- Accepted argument types: `string_t`
- Rejected inputs: non-string values outside explicit generator/runtime conversion sites

## Behavior
- Returns the byte length of the input string.
- Current contract is byte-oriented and binary-safe.
- v0.2 catalog binding: `scpp::str::byte_length`; `strlen("é中😀")` is `9`.
- The older strict S2S binding still targets code-point-counting `str::length`.
  It is a documented implementation inconsistency, not v0.2's contract. The portable
  compiler's existing managed-text helpers are unchanged; Unicode length API and
  cross-surface reconciliation are deferred to CH15.

## Compatibility table
- PHP returns string byte length â†’ Prism++ returns string byte length â†’ kept
- PHP accepts wider scalar coercions in some contexts â†’ Prism++ keeps the typed string-facing wrapper contract â†’ modified

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: compute string size once from `string_t`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- v0.2 implementation: `runtime/include/modules/strings/strings.hpp`.
- v0.2 registration: `runtime/metadata/modules/core.json`.
- Older S2S profiles retain their separate symbol maps pending migration.

## Test matrix
- empty string
- non-empty string
- binary-safe byte count
