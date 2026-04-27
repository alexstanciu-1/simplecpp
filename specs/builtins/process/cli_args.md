# Builtin Contract - `cli_args`
Doc Status: normative

- Name: `cli_args`
- Module class: `process`
- Source-language reference target: Prism++ CLI helper alias
- Current compatibility level: narrow
- Current status: active

## Supported form

```php
cli_args(): mixed
```

Current result shape:

- returns the same value as `cli_argv()`
- provided as a shorter alias for function-style CLI argument access

## Current notes

- `cli_args()` and `cli_argv()` are equivalent in the current pass
- prefer one style consistently within a codebase

## Runtime placement

Generator-facing calls lower to:

```cpp
php::cli_args()
```

Current runtime owner:

- `runtime/include/lang/php/php_process.hpp`
