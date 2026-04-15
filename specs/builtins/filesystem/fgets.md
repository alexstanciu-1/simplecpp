# Builtin Contract — `fgets`

## Identity
- Name: `fgets`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fgets`
- Compatibility level: practical

## Signature
- Supported forms:
  - `fgets(resource $stream): nullable<string>`
  - `fgets(resource $stream, int $length): nullable<string>`
- Accepted argument types: nullable resource handle, optional int_t

## Behavior
- Reads one line from an open readable file-stream resource.
- Without `length`, repeatedly reads chunks until newline or EOF.
- With `length`, reads up to the requested buffer width using stdio semantics.
- EOF before any bytes are read returns `null`.
- A partial final line without trailing newline is still returned as a string.

## Compatibility table
- PHP returns a line string or `false` → Prism++ returns line string or `null` → modified
- PHP length handling is broader and warning-heavy → Prism++ keeps a narrowed positive-length contract → modified

## Error policy
- Non-readable stream throws a runtime error.
- Non-positive explicit length throws a runtime error.
- Null resource, wrong resource kind, and closed-resource use throw runtime errors.

## Runtime and wrapper split
Runtime: validate readable file resource and use `std::fgets` in one-shot or chunked mode.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- line with newline
- line without newline at EOF
- length-limited read
- EOF before reading returns null
