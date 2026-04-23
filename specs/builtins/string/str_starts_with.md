# Builtin Contract â€” `str_starts_with`
Doc Status: normative
## Identity
- Name: `str_starts_with`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `str_starts_with`
- Compatibility level: practical

## Signature
- Supported form: `str_starts_with(string $haystack, string $needle): bool`
- Accepted argument types: `string_t`, `string_t`

## Behavior
- Returns `true` when `haystack` begins with `needle`.
- Empty `needle` returns `true`.
- Current contract is byte-oriented.

## Compatibility table
- PHP prefix check â†’ Prism++ prefix check â†’ kept
- Empty needle returns `true` â†’ Prism++ does the same â†’ kept

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: prefix compare.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- matching prefix
- empty needle
- non-matching prefix
