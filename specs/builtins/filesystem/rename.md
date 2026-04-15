# Builtin Contract — `rename`

## Identity
- Name: `rename`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `rename`
- Compatibility level: practical

## Signature
- Supported form: `rename(string $source, string $dest): bool`
- Accepted argument types: string_t, string_t

## Behavior
- Renames or moves a filesystem entry.
- Success returns `true`; failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` → Prism++ returns `true`/`false` → modified

## Error policy
- Does not throw for ordinary rename failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::rename` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- rename existing file
- old path missing afterward
- missing source returns false
