# JSON builtins â€” first pass
Doc Status: normative
This document defines the first-pass narrowed contract for the Prism++ / Simple C++ JSON builtins.

See also:
- `specs/builtins/json/README.md`
- one-contract-per-builtin files in `specs/builtins/json/`
- `docs/json_builtins.md`

## Module split

- `runtime/include/modules/json/json.hpp`

Support implementation lives in:

- `runtime/include/modules/json/json.cpp`

The goal is to keep JSON isolated from generic `php.hpp` growth, similar to filesystem and stdio.

## Runtime value target

`json_decode()` carries successful values in the same runtime value model that hand-written Simple C++ / Prism++ code would build.

That means:

- `null` -> inline `null_t` inside `mixed_t`
- booleans -> inline `bool_t`
- integers -> inline `int_t`
- fractional/exponent numbers -> inline `float_t`
- strings -> inline `string_t`
- arrays -> `dynamic_t` backed by shared packed `hash_t<mixed_t>` storage
- objects -> `dynamic_t` backed by shared associative `hash_t<mixed_t>` storage

This deliberately avoids a separate JSON-only AST or separate array/object container classes.

## Contract narrowing

This first pass intentionally does **not** aim for full PHP `json_*` parity.

Key decisions:

- only the core `json_decode(string)` and `json_encode(value)` shapes are implemented
- decoding returns `result<mixed>`; malformed input is an error branch handled with `take`, distinct from valid JSON `null` or `false`
- object-vs-array semantics follow `hash_t::is_packed()`
- numeric-looking object keys normalize through existing `hash_t` key rules, matching normal runtime construction
- `json_encode()` returns `result<string>`; non-finite floats produce its error branch
- weak tables produce the error branch of `json_encode()`
- no options bitmask or associative/object toggles are implemented yet

## Testing note

Runtime smoke coverage lives in `tests/runtime/native/test_json.cpp`.
