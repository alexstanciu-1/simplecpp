# Builtin Contract â€” `copy`
Doc Status: normative
## Identity
- Name: `copy`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `copy`
- Compatibility level: practical

## Signature
- Supported form: `copy(string $source, string $dest): bool`
- Accepted argument types: string_t, string_t

## Behavior
- Copies one file to another path using overwrite-existing semantics.
- Success returns `true`; failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` â†’ Prism++ returns `true`/`false` â†’ modified
- PHP has broader stream/context behavior â†’ Prism++ currently supports direct path-to-path copy only â†’ modified

## Error policy
- Does not throw for ordinary copy failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::copy_file` with `overwrite_existing`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- copy file and verify destination exists
- missing source returns false
