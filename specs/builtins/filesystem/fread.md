# Builtin Contract â€” `fread`
Doc Status: normative
## Identity
- Name: `fread`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fread`
- Compatibility level: practical

## Signature
- Supported form: `fread(resource $stream, int $length): nullable<string>`
- Accepted argument types: nullable resource handle, int_t

## Behavior
- Reads up to `length` bytes from an open readable file-stream resource.
- Length `0` returns the empty string.
- Partial reads return the bytes actually read.
- EOF after no bytes are read returns the empty string.
- Read error returns `null`.

## Compatibility table
- PHP returns bytes read or `false` â†’ Prism++ returns bytes read or `null` â†’ modified
- PHP distinguishes EOF from failure via empty string / `false` â†’ Prism++ keeps the same practical distinction with empty string / `null` â†’ modified

## Error policy
- Negative length throws a runtime error.
- Non-readable stream throws a runtime error.
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate readable file resource, allocate a byte buffer, call `std::fread`, and resize to the actual byte count.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- full chunk read
- partial read near EOF
- zero-length read returns empty string
- EOF probe returns empty string
