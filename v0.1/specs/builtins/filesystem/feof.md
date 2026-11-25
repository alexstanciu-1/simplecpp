# Builtin Contract â€” `feof`
Doc Status: normative
## Identity
- Name: `feof`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `feof`
- Compatibility level: practical

## Signature
- Supported form: `feof(resource $stream): bool`
- Accepted argument types: nullable resource handle

## Behavior
- Reports whether EOF is currently set on an open file-stream resource.
- Returns `false` before EOF and `true` after an EOF-triggering read probe.

## Compatibility table
- PHP returns boolean EOF state â†’ Prism++ keeps boolean EOF state â†’ kept

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource and call `std::feof`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- false before EOF
- true after EOF read probe
