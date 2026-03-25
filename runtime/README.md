# Generated Simple C++ runtime

This runtime was generated from:

- `specs/spec.md`
- `specs/config.json`

## Notes

- namespace: `scpp`
- default `create()` ownership: `shared_p<T>`
- explicit inline storage helper: `value<T>(...) -> value_p<T>`
- `value_p<T>` stays object-like at the usage surface, so member access continues through `->`
- explicit references are emitted directly as native C++ references (`T&` / `shared_p<T>&`)
- comparison result type: `bool_t`
- conditions in generated code should use `.native_value()`
- this package is intentionally conservative and close to the provided config

## Layout

- `include/scpp/runtime.hpp` umbrella include
- one header per runtime type
- `src/runtime.cpp` placeholder translation unit

- custom empty-optional sentinel: `scpp::nullopt_t` / `scpp::nullopt`

- custom empty-pointer sentinel: `scpp::nullptr_t` / `scpp::null_ptr`


## Current semantic notes

- `unset(...)` is intended only for nullable / pointer-like / handle-like values that can represent an empty state
- non-nullable value types, containers, and native references should not rely on `unset(...)`
- for non-nullable reset/cleanup, use `clean(...)` as the current project-level direction
- native C++ references are the reduced alias/reference feature and do not attempt to fully mimic PHP reference-binding semantics
