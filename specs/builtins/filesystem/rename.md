# Builtin Contract — `rename`

## Identity
- Name: `rename`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `rename`
- Compatibility level: practical

## Signature
- Supported form: `rename(string $source, string $dest): nullable<bool>`
- Accepted argument types: string_t, string_t

## Behavior
- Renames or moves a filesystem entry.
- Success returns `true`; failure returns `null`.

## Compatibility table
- PHP returns `true`/`false` → Prism++ returns `true`/`null` → modified

## Error policy
- Does not throw for ordinary rename failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::rename` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- rename existing file
- old path missing afterward
- missing source returns null
