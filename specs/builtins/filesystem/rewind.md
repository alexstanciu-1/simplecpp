# Builtin Contract — `rewind`

## Identity
- Name: `rewind`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `rewind`
- Compatibility level: practical

## Signature
- Supported form: `rewind(resource $stream): nullable<bool>`
- Accepted argument types: nullable resource handle

## Behavior
- Rewinds an open file-stream resource to the start.
- Success returns `true`.

## Compatibility table
- PHP returns `true` on success and `false` on failure → Prism++ returns `true` on success and throws for invalid resource use → modified

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource, call `std::rewind`, and clear error state.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- rewind after write/read cycle returns true
