# Builtin Contract — `is_link`

## Identity
- Name: `is_link`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `is_link`
- Compatibility level: practical

## Signature
- Supported form: `is_link(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Returns whether the path currently names a symbolic link.
- Operational filesystem errors collapse to `false` in this first pass.

## Compatibility table
- PHP returns bool → Prism++ returns bool → kept

## Error policy
- Does not throw for ordinary path lookup failures.

## Runtime and wrapper split
Runtime: call `std::filesystem::symlink_status` and `is_symlink` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- non-link false
- link true where host supports symlinks
