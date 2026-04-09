# Builtin Contract — `lcfirst`

## Identity
- Name: `lcfirst`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `lcfirst`
- Compatibility level: narrow

## Signature
- Supported form: `lcfirst(string $value): string`
- Accepted argument types: `string_t`

## Behavior
- Lowercases only the first byte of the string.
- Current contract is ASCII/byte-oriented, not Unicode-aware.

## Compatibility table
- PHP lowercases the first character → Prism++ lowercases the first byte → modified
- Empty input returns empty output → Prism++ does the same → kept

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: transform first byte only when present.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- non-empty ASCII input
- empty string
