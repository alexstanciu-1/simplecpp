# Builtin Contract â€” `ftell`
Doc Status: normative
## Identity
- Name: `ftell`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `ftell`
- Compatibility level: practical

## Signature
- Supported form: `ftell(resource $stream): nullable<int>`
- Accepted argument types: nullable resource handle

## Behavior
- Returns the current byte offset for an open file-stream resource.
- Operational tell failure returns `null`.

## Compatibility table
- PHP returns the current offset or `false` â†’ Prism++ returns offset or `null` â†’ modified

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource and call `std::ftell`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- position after `fgets` line read
- position after `rewind` + read cycle
