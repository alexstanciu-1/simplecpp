# Builtin Contract — `copy`

## Identity
- Name: `copy`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `copy`
- Compatibility level: practical

## Signature
- Supported form: `copy(string $source, string $dest): nullable<bool>`
- Accepted argument types: string_t, string_t

## Behavior
- Copies one file to another path using overwrite-existing semantics.
- Success returns `true`; failure returns `null`.

## Compatibility table
- PHP returns `true`/`false` → Prism++ returns `true`/`null` → modified
- PHP has broader stream/context behavior → Prism++ currently supports direct path-to-path copy only → modified

## Error policy
- Does not throw for ordinary copy failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::copy_file` with `overwrite_existing`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- copy file and verify destination exists
- missing source returns null
