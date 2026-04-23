# Builtin Contract â€” `fclose`
Doc Status: normative
## Identity
- Name: `fclose`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fclose`
- Compatibility level: practical

## Signature
- Supported form: `fclose(resource $stream): nullable<bool>`
- Accepted argument types: nullable resource handle

## Behavior
- Closes an open file-stream resource.
- Success returns `true`.
- The resource wrapper is marked closed after the close attempt.
- Subsequent use of the resource throws.

## Compatibility table
- PHP returns `true`/`false` â†’ Prism++ returns `true`/`null` and enforces closed-resource throwing on later use â†’ modified

## Error policy
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate resource, call `std::fclose`, and mark the wrapper closed.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- successful close
- subsequent read throws
