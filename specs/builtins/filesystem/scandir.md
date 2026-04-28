# Builtin Contract â€” `scandir`
Doc Status: normative
## Identity
- Name: `scandir`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `scandir`
- Compatibility level: practical

## Signature
- Supported form: `scandir(string $path): result_or_false<hash_t<mixed_t>>`
- Accepted argument types: string_t

## Behavior
- Returns actual entry names only for the target directory.
- Entry names are sorted lexicographically ascending.
- `.` and `..` are excluded by design.
- Failure returns `false`.
- The success payload is directly iterable by PHP `foreach` through wrapper iterable delegation.

## Compatibility table
- PHP includes `.` and `..` in the default result â†’ Prism++ excludes them deliberately â†’ modified
- PHP returns array or `false` â†’ Prism++ now returns packed `hash_t<mixed_t>` or `false` at the PHP exposure layer â†’ aligned

## Error policy
- Does not throw for ordinary missing-path or not-a-directory failures.

## Runtime and wrapper split
Runtime: iterate the directory with `std::filesystem::directory_iterator`, collect filename components, sort ascending, and pack into `hash_t<mixed_t>`.
- Wrapper: expose PHP-visible name only.

## Consumption rules
- `take($files, scandir($path))` is the preferred explicit extraction form when the code needs a typed success-payload local.
- `foreach (scandir($path) as $entry)` is allowed and iterates the carried success payload directly.
- If the call is in the `false` branch, direct iteration fails at runtime on wrapper unwrap rather than silently producing zero entries.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in shared filesystem runtime support under `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`) with PHP wrapper exposure in `runtime/include/lang/php/php_filesystem.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- sorted names
- excludes dot entries by construction
- missing path returns false
