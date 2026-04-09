# Builtin Contract — `file_put_contents`

## Identity
- Name: `file_put_contents`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `file_put_contents`
- Compatibility level: practical

## Signature
- Supported form: `file_put_contents(string $path, string $data): nullable<int>`
- Accepted argument types: string_t, string_t

## Behavior
- Writes the full byte contents to `path` in overwrite/truncate mode.
- Creates the file when the parent directory exists.
- Returns written byte count on success.
- Operational open/write failure returns `null`.
- Append and flags are out of scope in this first pass.

## Compatibility table
- PHP returns bytes written or `false` → Prism++ returns bytes written or `null` → modified
- PHP supports flags and broader input coercions → Prism++ currently supports overwrite-only string writes → modified

## Error policy
- Does not throw for ordinary open/write failure.

## Runtime and wrapper split
Runtime: open a binary output stream with truncate mode, write bytes, and return written size.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- successful write
- overwrite existing file
- missing parent returns null
