# Builtin Contract — `fputs`

## Identity
- Name: `fputs`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fputs`
- Compatibility level: practical

## Signature
- Supported form: `fputs(resource $stream, string $data): nullable<int>`
- Accepted argument types: nullable resource handle, string_t

## Behavior
- Alias of `fwrite` in the first-pass contract.
- Returns written byte count on success and `null` on operational failure.

## Compatibility table
- PHP treats `fputs` as an alias of `fwrite` → Prism++ keeps the same aliasing model → kept

## Error policy
- Matches `fwrite` error policy.

## Runtime and wrapper split
Runtime: direct alias to `fwrite`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- alias write path behaves like `fwrite`
