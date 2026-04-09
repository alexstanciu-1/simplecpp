# Builtin Contract — `str_starts_with`

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
- PHP prefix check → Prism++ prefix check → kept
- Empty needle returns `true` → Prism++ does the same → kept

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: prefix compare.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- matching prefix
- empty needle
- non-matching prefix
