# Builtin Contract — `ltrim`

## Identity
- Name: `ltrim`
- Module/family: string
- Category/classification: Core Utility Wrapper
- Status: experimental
- Source-language reference target: PHP `ltrim`
- Compatibility level: practical

## Signature
- Supported forms:
  - `ltrim(string $value): string`
  - `ltrim(string $value, string $mask): string`
- Accepted argument types: `string_t`, optional `string_t`

## Behavior
- Removes matching bytes from the start of the string only.
- Omitted mask uses the default PHP whitespace byte set: space, `\n`, `\r`, `\t`, `\v`, `\0`.
- Provided mask is interpreted as a set of bytes, not a substring.
- Empty mask leaves the input unchanged.

## Compatibility table
- PHP default trim whitespace set → Prism++ uses the same default byte set → kept
- PHP custom second argument works as a character mask/set → Prism++ uses byte-set semantics → kept
- PHP supports richer charlist edge semantics → current Prism++ contract stays at byte-set trimming only → modified

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: left-boundary byte-set scan.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- default whitespace trimming
- explicit custom mask trimming
- empty mask
