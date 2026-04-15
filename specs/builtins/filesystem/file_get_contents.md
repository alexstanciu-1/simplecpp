# Builtin Contract — `file_get_contents`

## Identity
- Name: `file_get_contents`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `file_get_contents`
- Compatibility level: practical

## Signature
- Supported form: `file_get_contents(string $path): result_or_false<string>`
- Accepted argument types: string_t

## Behavior
- Reads the full file contents as a byte-preserving string.
- Empty file returns the empty string.
- Operational open/read failure returns `false`.

## Compatibility table
- PHP returns string or `false` → Prism++ now returns string or `false` at the PHP exposure layer → aligned

## Error policy
- Does not throw for ordinary missing-path or open failures.

## Runtime and wrapper split
Runtime: open a binary input stream, read the full file payload, and return `false` on open/bad failures.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- non-empty file
- empty file
- missing file returns false
