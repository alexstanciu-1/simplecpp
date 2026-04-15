# Builtin Contract — `str_replace`

## Identity
- Name: `str_replace`
- Module/family: string
- Category/classification: Replace-All Wrapper
- Status: experimental
- Source-language reference target: PHP `str_replace`
- Compatibility level: practical

## Signature
- Supported form: `str_replace(string $search, string $replace, string $subject): string`
- Accepted argument types: `string_t`, `string_t`, `string_t`
- Current scope: string-only form

## Behavior
- Replaces all non-overlapping occurrences of `search` inside `subject`.
- Replacement is byte-oriented and binary-safe.
- Empty `search` returns the original subject unchanged.

## Compatibility table
- PHP supports array forms and count output → Prism++ currently supports only the string-only form → modified
- PHP replaces all non-overlapping matches → Prism++ keeps the same practical direction → kept

## Error policy
- No runtime error for valid typed inputs.

## Runtime and wrapper split
- Runtime: run a deterministic replace-all scan loop.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- no matches
- single-byte search
- multi-byte search
- empty search no-op
- repeated non-overlapping matches
