# Builtin Contract - `cli_argv`
Doc Status: normative

- Name: `cli_argv`
- Module class: `process`
- Source-language reference target: Prism++ CLI helper
- Current compatibility level: narrow
- Current status: active

## Supported form

```php
cli_argv(): mixed
```

Current result shape:

- returns the PHP-style CLI argument array as a dynamic/mixed value
- index `0` is the program name
- user arguments start at index `1`

For v0.2 this is specifically a mixed-family list with mixed string entries, not
a dynamic-family container. Each call returns an independent value snapshot;
mutating it does not change the process arguments or another query's result.
Empty arguments and spaces are preserved. Generated executable startup initializes
the runtime context before authored entry code; calls inside functions and methods
observe the same context across translation units.

## Current notes

- intended for use inside functions and methods where `$argv` is not being passed explicitly
- callers should stabilize reads at typed boundaries when needed

## Runtime placement

Generator-facing calls lower to:

```cpp
php::cli_argv()
```

Current runtime owner:

- `runtime/include/lang/php/php_process.hpp`
