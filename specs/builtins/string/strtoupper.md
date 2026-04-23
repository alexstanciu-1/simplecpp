# Builtin Contract â€” `strtoupper`
Doc Status: normative
## Identity
- Name: `strtoupper`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `strtoupper`
- Compatibility level: narrow

## Signature
- Supported form: `strtoupper(string $value): string`
- Accepted argument types: `string_t`

## Behavior
- Applies byte-wise uppercase conversion.
- Current contract is ASCII/`std::toupper`-style byte conversion, not full Unicode case folding.

## Compatibility table
- PHP uppercases strings â†’ Prism++ uppercases strings â†’ kept
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
