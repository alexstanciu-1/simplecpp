# Builtin Contract — `implode`

## Identity
- Name: `implode`
- Module/family: string
- Category/classification: Join Wrapper
- Status: experimental
- Source-language reference target: PHP `implode`
- Compatibility level: practical

## Signature
- Supported forms:
  - `implode(string $separator, hash_t<string_t> $pieces): string`
  - `implode(string $separator, vector_t<string_t> $pieces): string`
- Accepted argument types: `string_t` separator with `hash_t<string_t>` or `vector_t<string_t>` pieces
- Current scope: canonical separator-first form only

## Behavior
- Joins element values in container iteration order using `separator` between elements.
- Empty input returns the empty string.
- `hash_t<string_t>` support uses stored entry iteration order.
- `vector_t<string_t>` support is included as a practical extension.
- Behavior is byte-oriented and binary-safe.

## Compatibility table
- PHP accepts array input in canonical and legacy-swapped forms → Prism++ supports only canonical separator-first form → modified
- PHP may coerce non-string elements → Prism++ currently supports string elements only → modified
- Empty input joins to `""` → Prism++ keeps the same behavior → kept

## Error policy
- No runtime error for valid typed inputs.

## Runtime and wrapper split
- Runtime: iterate the container once and join string values.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- packed hash join
- associative hash join
- vector join
- empty input
