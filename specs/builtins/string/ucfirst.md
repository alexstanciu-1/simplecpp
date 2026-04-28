# Builtin Contract â€” `ucfirst`
Doc Status: normative
## Identity
- Name: `ucfirst`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `ucfirst`
- Compatibility level: narrow

## Signature
- Supported form: `ucfirst(string $value): string`
- Accepted argument types: `string_t`

## Behavior
- Uppercases only the first byte of the string.
- Current contract is ASCII/byte-oriented, not Unicode-aware.

## Compatibility table
- PHP uppercases the first character â†’ Prism++ uppercases the first byte â†’ modified
- Empty input returns empty output â†’ Prism++ does the same â†’ kept

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: transform first byte only when present.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- non-empty ASCII input
- empty string
