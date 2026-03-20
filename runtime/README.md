# Generated Simple C++ runtime

This runtime was generated from:

- `specs/spec.md`
- `specs/config.json`

## Notes

- namespace: `scpp`
- default `create()` ownership: `shared_p<T>`
- comparison result type: `bool_t`
- conditions in generated code should use `.native_value()`
- this package is intentionally conservative and close to the provided config

## Layout

- `include/scpp/runtime.hpp` umbrella include
- one header per runtime type
- `src/runtime.cpp` placeholder translation unit

- custom empty-optional sentinel: `scpp::nullopt_t` / `scpp::nullopt`

- custom empty-pointer sentinel: `scpp::nullptr_t` / `scpp::null_ptr`
