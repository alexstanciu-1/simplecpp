# Builtin Contract â€” `mkdir`
Doc Status: normative
## Identity
- Name: `mkdir`
- Module/family: filesystem
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `mkdir`
- Compatibility level: practical

## Signature
- Supported form: `mkdir(string $path): bool`
- Accepted argument types: string_t

## Behavior
- Creates one directory at the target path.
- Success returns `true`.
- Operational failure, including already-existing target, returns `false`.

## Compatibility table
- PHP returns `true`/`false` and has richer mode/recursive options â†’ Prism++ currently supports only a narrowed single-directory form with `true`/`false` â†’ modified

## Error policy
- Does not throw for ordinary create failure.

## Runtime and wrapper split
Runtime: call `std::filesystem::create_directory` with `std::error_code`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Filesystem and stdio surfaces are intentionally split into dedicated headers, not folded into the generic `php.hpp` surface.
- Filesystem wrappers are available when the dedicated filesystem/stdio module is included by project policy.

## Compile plan summary
Implemented in `runtime/include/scpp/support/filesystem module (scpp::filesystem).hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- create new directory
- existing directory returns false
