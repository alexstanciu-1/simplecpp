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
- `include/scpp/runtime.hpp` is the mandatory public entry point for generated operator availability
- one header per runtime type
- `src/runtime.cpp` placeholder translation unit

- custom empty-optional sentinel: `scpp::nullopt_t` / `scpp::nullopt`

- custom empty-pointer sentinel: `scpp::nullptr_t` / `scpp::null_ptr`


## Current semantic notes

- `unset(...)` is intended only for nullable / pointer-like / handle-like values that can represent an empty state
- non-nullable value types, containers, and native references should not rely on `unset(...)`
- for non-nullable reset/cleanup, use `clean(...)` as the current project-level direction
- native C++ references are the reduced alias/reference feature and do not attempt to fully mimic PHP reference-binding semantics


## hash_t notes

- `scpp::hash_t` is the runtime container used for PHP `array` lowering.
- public include stays `scpp/hash_t.hpp`; implementation lives under `include/scpp/support/`.
- `find()` is the non-inserting lookup path.
- `at()` is checked non-inserting access with throw-style semantics.
- generator-facing non-assignment dim access is non-mutating and lowers through `mixed_t::get(...)` / `hash_t<mixed_t>::_find_val(...)`, so plain reads stay null-on-miss without autovivification.

## Language target

The runtime supports a compile-time PHP language target via `-DSCPP_LANGUAGE_TARGET_PHP=1`.
When enabled, array-key handling applies PHP-target key normalization for decimal integer strings like `"10" -> 10` and `"-3" -> -3`, while strings like `"08"`, `" 10"`, `"10 "`, and `"+10"` stay strings. Append continues from the maximum integer key.


## Operator generation notes

The runtime now has a generated operator-surface direction.

Current procedure and rationale are documented in:

- `runtime/specs/operator_generation_flow.md`

Current intent:

- operator families are provisioned by a PHP generation tool from `runtime/specs/config.json`
- generated public operators live under `include/scpp/generated/`
- individual wrapper headers are no longer expected to remain operator-complete after migration
- handwritten concepts provide stable operand-category buckets
- internal `detail::...` helpers implement the native-wrapper and mixed runtime paths
- competing public operators elsewhere should be removed only after the generated family fully covers them
