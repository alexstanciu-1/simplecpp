# Builtin Contract â€” `strtolower`
Doc Status: normative
## Identity
- Name: `strtolower`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `strtolower`
- Compatibility level: narrow

## Signature
- Supported form: `strtolower(string $value): string`
- Accepted argument types: `string_t`

## Behavior
- Applies byte-wise lowercase conversion.
- Current contract is ASCII/`std::tolower`-style byte conversion, not full Unicode case folding.

## Compatibility table
- PHP lowercases strings â†’ Prism++ lowercases strings â†’ kept
- PHP string semantics can be locale/encoding-sensitive in broader ecosystems â†’ Prism++ keeps byte-wise runtime behavior only â†’ modified

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: byte transform over `string_t`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- mixed-case ASCII input
- empty string
