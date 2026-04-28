# Builtin Contract - `cli_argc`
Doc Status: normative

- Name: `cli_argc`
- Module class: `process`
- Source-language reference target: Prism++ CLI helper
- Current compatibility level: narrow
- Current status: active

## Supported form

```php
cli_argc(): int
```

## Current notes

- returns the current executable argument count
- intended for use inside functions and methods where `$argc` is not being passed explicitly
- top-level executable code may still use `$argc` directly

## Runtime placement

Generator-facing calls lower to:

```cpp
php::cli_argc()
```

Current runtime owner:

- `runtime/include/lang/php/php_process.hpp`
