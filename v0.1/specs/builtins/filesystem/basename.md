# Builtin Contract â€” `basename`
Doc Status: normative
## Identity
- Name: `basename`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `basename`
- Compatibility level: practical

## Signature
- Supported form: `basename(string $path): string`
- Accepted argument types: string_t

## Behavior
- Returns the filename component using the current first-pass path parsing rules.
- No extra PHP edge-case normalization is attempted in this pass.

## Compatibility table
- PHP has richer path-edge behavior â†’ Prism++ currently returns `std::filesystem::path(path).filename().string()` â†’ modified

## Error policy
- Does not throw for ordinary typed input.

## Runtime and wrapper split
Runtime: compute `filename()` from `std::filesystem::path`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- nested path basename extraction
