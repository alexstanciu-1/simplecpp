# Builtin Contract - `shell_exec`
Doc Status: normative

- Name: `shell_exec`
- Module class: `process`
- Source-language reference target: PHP `shell_exec`
- Current compatibility level: narrow
- Current status: active

## Supported form

```php
shell_exec(string $command): mixed
```

Current narrowed result states:

- returns `string` on successful pipe creation; empty output remains `""`
- returns `false` when the command pipe cannot be established

## Current notes

- the current PHP surface exposes `shell_exec()` as a PHP-like falseable string producer through `mixed`
- explicit `=== false` checks are preferred when failure handling matters
- command interpretation is delegated to the host shell
- this first pass does not freeze cross-platform command syntax compatibility beyond the host shell behavior

## Runtime placement

Generator-facing calls lower to:

```cpp
php::shell_exec(...)
```

Current runtime owner:

- `runtime/include/lang/php/php_process.hpp`

## Minimum test expectations

- success command with non-empty stdout
- success command with empty stdout
- pipe-open failure returns `false`
