# Builtin Contract — `fseek`

## Identity
- Name: `fseek`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fseek`
- Compatibility level: practical

## Signature
- Supported forms:
  - `fseek(resource $stream, int $offset): nullable<int>`
  - `fseek(resource $stream, int $offset, int $whence): nullable<int>`
- Accepted argument types: nullable resource handle, int_t, optional int_t

## Behavior
- Seeks on an open file-stream resource using `SEEK_SET` by default.
- Success returns integer `0`.
- Operational seek failure returns `null`.

## Compatibility table
- PHP returns `0` on success and `-1` on failure → Prism++ returns `0` on success and `null` on operational failure → modified

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource, delegate to `std::fseek`, clear error state on success.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- seek to start
- seek with explicit whence
- seek after EOF probe resets state
- closed-resource call throws
