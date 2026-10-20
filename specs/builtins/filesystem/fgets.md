# Builtin Contract â€” `fgets`
Doc Status: normative
## Identity
- Name: `fgets`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fgets`
- Compatibility level: practical

## Signature
- Supported forms:
  - `fgets(resource $stream): result_or_false<string>`
  - `fgets(resource $stream, int $length): result_or_false<string>`
- Accepted argument types: nullable resource handle, optional int_t

## Behavior
- Reads one line from an open readable file-stream resource.
- Without `length`, reads bytes until newline or EOF, preserving embedded NUL.
- With positive `length`, reads at most `length - 1` bytes. Width one returns a present empty string without advancing.
- EOF before any bytes are read returns the false sentinel.
- A partial final line without trailing newline is still returned as a string.

## Compatibility table
- PHP returns a line string or `false` â†’ Prism++ returns line string or `false` â†’ modified
- PHP length handling is broader and warning-heavy â†’ Prism++ keeps a narrowed positive-length contract â†’ modified

## Error policy
- Non-readable stream throws a runtime error.
- Non-positive explicit length throws a runtime error.
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate the readable file resource and accumulate bytes through buffered stdio reads.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/core/stdio.hpp`; strict source exposure follows [strict IO](strict_io.md).
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- line with newline
- line without newline at EOF
- length-limited read
- EOF before reading returns false
