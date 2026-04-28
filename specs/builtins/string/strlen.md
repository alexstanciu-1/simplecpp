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
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- empty string
- non-empty string
- binary-safe byte count
