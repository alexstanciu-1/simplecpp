# Builtin Contract â€” `touch`
Doc Status: normative
## Identity
- Name: `touch`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `touch`
- Compatibility level: practical

## Signature
- Supported form: `touch(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Updates last-write time when the path exists.
- Creates an empty file when the path does not exist and the parent directory exists.
- Success returns `true`; failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` and supports timestamp arguments â†’ Prism++ currently supports the zero-argument time-now form with `true`/`false` â†’ partially aligned

## Error policy
- Does not throw for ordinary create/update failure.

## Runtime and wrapper split
Runtime: if the path exists, set `last_write_time` to now; otherwise create an empty file.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- create missing file
- update existing file timestamp
- missing parent returns false
