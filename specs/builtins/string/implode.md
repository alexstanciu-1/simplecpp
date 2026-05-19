# Builtin Contract â€” `implode`
Doc Status: normative
## Identity
- Name: `implode`
- Module/family: string
- Category/classification: Join Wrapper
- Status: experimental
- Source-language reference target: PHP `implode`
- Compatibility level: practical

## Signature
- Supported forms:
  - `implode(string $separator, hash<string> $pieces): string`
  - `implode(string $separator, vector<string> $pieces): string`
  - `implode(string $separator, mixed_t $pieces): string` when runtime kind is array-like
- Accepted argument types: `string_t` separator with `hash<string>`, `vector<string>`, or `mixed_t` array-like pieces
- Current scope: canonical separator-first form only

## Behavior
- Joins element values in container iteration order using `separator` between elements.
- Empty input returns the empty string.
- `hash<string>` support uses stored entry iteration order.
- `vector<string>` support is included as a practical extension.
- `mixed_t` support is accepted only when the runtime kind is array-like.
- Behavior is byte-oriented and binary-safe.

## Compatibility table
- PHP accepts array input in canonical and legacy-swapped forms â†’ Prism++ supports only canonical separator-first form â†’ modified
- PHP may coerce non-string elements â†’ Prism++ currently supports string elements only â†’ modified
- Empty input joins to `""` â†’ Prism++ keeps the same behavior â†’ kept

## Error policy
- No runtime error for valid typed inputs.
- `mixed_t` input must throw a runtime error when the runtime kind is not array-like.

## Runtime and wrapper split
- Runtime: iterate the container once and join string values.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- packed hash join
- associative hash join
- vector join
- empty input
