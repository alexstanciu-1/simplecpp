# Builtin Contract — `unlink`

## Identity
- Name: `unlink`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `unlink`
- Compatibility level: practical

## Signature
- Supported form: `unlink(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Removes a file-system entry through `remove`.
- Success returns `true`; failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` → Prism++ returns `true`/`false` → modified

## Error policy
- Does not throw for ordinary remove failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::remove` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/php_filesystem.hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- remove existing file
- missing path returns false
