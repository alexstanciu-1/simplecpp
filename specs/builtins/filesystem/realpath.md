# Builtin Contract — `realpath`

## Identity
- Name: `realpath`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `realpath`
- Compatibility level: practical

## Signature
- Supported form: `realpath(string $path): nullable<string>`
- Accepted argument types: string_t

## Behavior
- Returns the absolute canonical path string.
- The path must exist in this first pass.
- Failure returns `null`.

## Compatibility table
- PHP returns canonical path or `false` → Prism++ returns canonical path or `null` → modified
- PHP can vary in edge handling by platform → Prism++ keeps strict existing-path canonicalization → modified

## Error policy
- Does not throw for ordinary missing-path failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::canonical` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- existing path canonicalizes
- missing path returns null
