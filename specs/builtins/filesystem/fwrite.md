# Builtin Contract â€” `fwrite`
Doc Status: normative
## Identity
- Name: `fwrite`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fwrite`
- Compatibility level: practical

## Signature
- Supported form: `fwrite(resource $stream, string $data): nullable<int>`
- Accepted argument types: nullable resource handle, string_t

## Behavior
- Writes the byte contents of `data` to an open writable file-stream resource.
- Success returns the number of bytes written.
- Partial writes return the partial byte count.
- Operational write failure returns `null`.

## Compatibility table
- PHP returns byte count or `false` â†’ Prism++ returns byte count or `null` â†’ modified

## Error policy
- Non-writable stream throws a runtime error.
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate writable file resource and call `std::fwrite`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- round-trip write and read-back
- empty-string write returns 0
- write to read-only resource throws
