# JSON builtins — first pass

This document defines the first-pass narrowed contract for the Prism++ / Simple C++ JSON builtins.

See also:
- `specs/builtins/json/README.md`
- one-contract-per-builtin files in `specs/builtins/json/`
- `docs/json_builtins.md`

## Module split

- `runtime/include/scpp/php_json.hpp`

Support implementation lives in:

- `runtime/include/scpp/support/php_json.hpp`
- `runtime/include/scpp/support/php_json.cpp`

The goal is to keep JSON isolated from generic `php.hpp` growth, similar to filesystem and stdio.

## Runtime value target

`json_decode()` returns the same runtime value model that hand-written Simple C++ / Prism++ code would build.

That means:

- `null` -> inline `null_t` inside `mixed_t`
- booleans -> inline `bool_t`
- integers -> inline `int_t`
- fractional/exponent numbers -> inline `float_t`
- strings -> inline `string_t`
- arrays -> `shared_p<hash_t<mixed_t>>` in packed mode
- objects -> `shared_p<hash_t<mixed_t>>` in non-packed mode

This deliberately avoids a separate JSON-only AST or separate array/object container classes.

## Contract narrowing

This first pass intentionally does **not** aim for full PHP `json_*` parity.

Key decisions:

- only the core `json_decode(string)` and `json_encode(value)` shapes are implemented
- invalid input throws instead of returning a warning/false style result
- object-vs-array semantics follow `hash_t::is_packed()`
- numeric-looking object keys normalize through existing `hash_t` key rules, matching normal runtime construction
- non-finite floats are rejected by `json_encode()`
- weak tables are rejected by `json_encode()`
- no options bitmask or associative/object toggles are implemented yet

## Testing note

Runtime smoke coverage lives in `runtime/tests/test_php_json.cpp`.
