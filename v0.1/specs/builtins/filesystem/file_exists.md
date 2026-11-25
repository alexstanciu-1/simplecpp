# Builtin Contract â€” `file_exists`
Doc Status: normative
## Identity
- Name: `file_exists`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `file_exists`
- Compatibility level: practical

## Signature
- Supported form: `file_exists(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Returns whether the path exists.
- Operational filesystem errors collapse to `false` in this first pass.

## Compatibility table
- PHP returns bool â†’ Prism++ returns bool â†’ kept

## Error policy
- Does not throw for ordinary path lookup failures.

## Runtime and wrapper split
Runtime: call `std::filesystem::exists` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- existing file true
- missing path false
