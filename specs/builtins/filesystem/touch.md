# Builtin Contract — `touch`

## Identity
- Name: `touch`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `touch`
- Compatibility level: practical

## Signature
- Supported form: `touch(string $path): nullable<bool>`
- Accepted argument types: string_t

## Behavior
- Updates last-write time when the path exists.
- Creates an empty file when the path does not exist and the parent directory exists.
- Success returns `true`; failure returns `null`.

## Compatibility table
- PHP returns `true`/`false` and supports timestamp arguments → Prism++ currently supports the zero-argument time-now form with `true`/`null` → modified

## Error policy
- Does not throw for ordinary create/update failure.

## Runtime and wrapper split
Runtime: if the path exists, set `last_write_time` to now; otherwise create an empty file.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- create missing file
- update existing file timestamp
- missing parent returns null
