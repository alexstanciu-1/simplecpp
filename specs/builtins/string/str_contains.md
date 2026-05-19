# Builtin Contract — `str_contains`
Doc Status: normative
## Identity
- Name: `str_contains`
- Module/family: string
- Category/classification: Pure Wrapper
- Status: experimental
- Source-language reference target: PHP `str_contains`
- Compatibility level: practical

## Signature
- Supported form: `str_contains(string $haystack, string $needle): bool`
- Accepted argument types: `string_t`, `string_t`

## Behavior
- Returns `true` when `needle` occurs anywhere within `haystack`.
- Empty `needle` returns `true`.
- Current contract is byte-oriented.

## Compatibility table
- PHP contains check → Prism++ contains check → kept
- Empty needle returns `true` → Prism++ does the same → kept

## Error policy
- No runtime error for valid typed string inputs.

## Runtime and wrapper split
- Runtime: substring search.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_strict.json`.

## Test matrix
- matching substring
- empty needle
- non-matching substring
