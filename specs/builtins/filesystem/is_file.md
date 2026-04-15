# Builtin Contract — `is_file`

## Identity
- Name: `is_file`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `is_file`
- Compatibility level: practical

## Signature
- Supported form: `is_file(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Returns whether the path currently names a regular file.
- Operational filesystem errors collapse to `false` in this first pass.

## Compatibility table
- PHP returns bool → Prism++ returns bool → kept

## Error policy
- Does not throw for ordinary path lookup failures.

## Runtime and wrapper split
Runtime: call `std::filesystem::is_regular_file` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- regular file true
- directory false
- missing path false
