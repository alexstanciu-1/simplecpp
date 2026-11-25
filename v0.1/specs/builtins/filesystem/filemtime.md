# Builtin Contract â€” `filemtime`
Doc Status: normative
## Identity
- Name: `filemtime`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `filemtime`
- Compatibility level: practical

## Signature
- Supported form: `filemtime(string $path): result_or_false<int>`
- Accepted argument types: string_t

## Behavior
- Returns the last-write timestamp as Unix seconds.
- Failure returns `false`.

## Compatibility table
- PHP returns timestamp or `false` â†’ Prism++ returns timestamp or `false` â†’ modified

## Error policy
- Does not throw for ordinary lookup failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::last_write_time`, convert to Unix seconds, and return `false` on failure.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- existing file returns positive timestamp
- touch can refresh timestamp
