# Builtin Contract — `filemtime`

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
- PHP returns timestamp or `false` → Prism++ returns timestamp or `false` → modified

## Error policy
- Does not throw for ordinary lookup failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::last_write_time`, convert to Unix seconds, and return `false` on failure.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- existing file returns positive timestamp
- touch can refresh timestamp
