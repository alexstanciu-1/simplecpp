# Builtin Contract — `explode`

## Identity
- Name: `explode`
- Module/family: string
- Category/classification: Split Wrapper
- Status: experimental
- Source-language reference target: PHP `explode`
- Compatibility level: practical

## Signature
- Supported forms:
  - `explode(string $separator, string $string): mixed_t<table>`
  - `explode(string $separator, string $string, int $limit): mixed_t<table>`
- Accepted argument types: `string_t`, `string_t`, optional `int_t`
- Return shape: `mixed_t` holding `hash_t<mixed_t>` with packed integer keys

## Behavior
- Splits `string` by the full `separator` string.
- Empty separator throws `ValueError`.
- Positive `limit` keeps at most that many result parts.
- `limit == 0` is treated like a single-part result.
- Negative `limit` drops that many trailing parts from the full split result.
- Behavior is byte-oriented and binary-safe.

## Compatibility table
- PHP returns a packed array of strings → Prism++ returns `mixed_t` containing `hash_t<mixed_t>` → modified
- PHP rejects empty separator → Prism++ keeps the same behavior → kept
- PHP supports positive and negative limits → Prism++ keeps the same practical behavior → kept

## Error policy
- Empty separator throws `ValueError`.
- No runtime error for valid typed inputs.

## Runtime and wrapper split
- Runtime: perform deterministic split and pack parts into `hash_t<mixed_t>`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- default split
- positive limit
- zero limit
- negative limit
- separator absent with negative limit
- empty separator error
