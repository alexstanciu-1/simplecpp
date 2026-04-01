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


## table_t notes

- `scpp::table_t` is the runtime container used for PHP `array` lowering.
- public include stays `scpp/table_t.hpp`; implementation lives under `include/scpp/support/`.
- `find()` is the non-inserting lookup path.
- `at()` is checked non-inserting access with throw-style semantics.
- generator-facing non-assignment dim access is non-mutating and lowers through `value_t::get(...)` / `table_t<value_t>::_find_val(...)`, so plain reads stay null-on-miss without autovivification.

## Language target

The runtime supports a compile-time PHP language target via `-DSCPP_LANGUAGE_TARGET_PHP=1`.
When enabled, array-key handling applies PHP-target key normalization for decimal integer strings like `"10" -> 10` and `"-3" -> -3`, while strings like `"08"`, `" 10"`, `"10 "`, and `"+10"` stay strings. Append continues from the maximum integer key.

