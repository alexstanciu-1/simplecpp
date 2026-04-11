# Builtin Contract — `fflush`

## Identity
- Name: `fflush`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fflush`
- Compatibility level: practical

## Signature
- Supported form: `fflush(resource $stream): bool`
- Accepted argument types: `result_or_false<resource_handle_t>` / file-stream resource handle

## Behavior
- Flushes an open file-stream resource.
- Success returns `true`.
- Operational flush failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` → Prism++ now returns `true`/`false` at the PHP exposure layer → aligned

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource and call `std::fflush`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- flush after write returns true
