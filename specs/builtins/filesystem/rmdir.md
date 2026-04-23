# Builtin Contract â€” `rmdir`
Doc Status: normative
## Identity
- Name: `rmdir`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `rmdir`
- Compatibility level: practical

## Signature
- Supported form: `rmdir(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Removes an empty directory.
- Success returns `true`; failure returns `false`.

## Compatibility table
- PHP returns `true`/`false` â†’ Prism++ returns `true`/`false` â†’ modified

## Error policy
- Does not throw for ordinary remove failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::remove` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- non-empty directory returns false
- empty directory removal succeeds
