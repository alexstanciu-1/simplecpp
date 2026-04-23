# Builtin Contract â€” `filesize`
Doc Status: normative
## Identity
- Name: `filesize`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `filesize`
- Compatibility level: practical

## Signature
- Supported form: `filesize(string $path): result_or_false<int>`
- Accepted argument types: string_t

## Behavior
- Returns file size in bytes for a regular file path.
- Failure returns `null`.

## Compatibility table
- PHP returns size or `false` â†’ Prism++ returns size or `false` â†’ modified

## Error policy
- Does not throw for ordinary lookup failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::file_size` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- known-size file
- missing path returns false
