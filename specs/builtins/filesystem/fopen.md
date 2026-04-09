# Builtin Contract — `fopen`

## Identity
- Name: `fopen`
- Module/family: filesystem / stdio
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `fopen`
- Compatibility level: practical

## Signature
- Supported form: `fopen(string $path, string $mode): nullable<resource<file_stream>>`
- Accepted argument types: string_t, string_t

## Behavior
- Parses a narrowed fopen mode set: `r`, `rb`, `r+`, `rb+`, `r+b`, `w`, `wb`, `w+`, `wb+`, `w+b`, `a`, `ab`, `a+`, `ab+`, `a+b`.
- Returns a nullable file-stream resource on success.
- Open failure returns `null`.
- The resource wrapper stores explicit `kind`, path, mode, readable/writable flags, and closed state.

## Compatibility table
- PHP returns `false` on ordinary open failure → Prism++ returns `null` → modified
- PHP accepts a broader mode surface → Prism++ supports the narrowed first-pass mode set above → modified

## Error policy
- Unsupported mode strings throw a runtime error.

## Runtime and wrapper split
Runtime: parse mode, call `std::fopen`, and create a `file_stream` resource wrapper.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_stdio.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- successful open in read/write mode
- open failure returns null
- unsupported mode throws
- resource kind is `file_stream` through the wrapper contract
