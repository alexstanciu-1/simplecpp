# Builtin Contract â€” `rtrim`
Doc Status: normative
## Identity
- Name: `rtrim`
- Module/family: string
- Category/classification: Core Utility Wrapper
- Status: experimental
- Source-language reference target: PHP `rtrim`
- Compatibility level: practical

## Signature
- Supported forms:
  - `rtrim(string $value): string`
  - `rtrim(string $value, string $mask): string`
- Accepted argument types: `string_t`, optional `string_t`

## Behavior
- Removes matching bytes from the end of the string only.
- Omitted mask uses the default PHP whitespace byte set: space, `\n`, `\r`, `\t`, `\v`, `\0`.
- Provided mask is interpreted as a set of bytes, not a substring.
- Empty mask leaves the input unchanged.

## Compatibility table
- PHP default trim whitespace set â†’ Prism++ uses the same default byte set â†’ kept
- PHP custom second argument works as a character mask/set â†’ Prism++ uses byte-set semantics â†’ kept
- PHP supports richer charlist edge semantics â†’ current Prism++ contract stays at byte-set trimming only â†’ modified

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: right-boundary byte-set scan.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- default whitespace trimming
- explicit custom mask trimming
- empty mask
