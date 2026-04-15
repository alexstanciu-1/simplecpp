# Builtin Contract — `scandir`

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

## Compatibility table
- PHP includes `.` and `..` in the default result → Prism++ excludes them deliberately → modified
- PHP returns array or `false` → Prism++ now returns packed `hash_t<mixed_t>` or `false` at the PHP exposure layer → aligned

## Error policy
- Does not throw for ordinary missing-path or not-a-directory failures.

## Runtime and wrapper split
Runtime: iterate the directory with `std::filesystem::directory_iterator`, collect filename components, sort ascending, and pack into `hash_t<mixed_t>`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `php_generator/specs/php_runtime_symbols.json`.

## Test matrix
- sorted names
- excludes dot entries by construction
- missing path returns false
