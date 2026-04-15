# JSON builtins — first pass

This page summarizes the first-pass Prism++ / Simple C++ JSON builtin surface.

JSON is now runtime-owned and lives under `namespace scpp::json`. The PHP layer keeps only a thin wrapper in `namespace scpp::php`.

## Header split

JSON support is intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated header instead:

- runtime module header: `runtime/include/modules/json/json.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_json.hpp`

## First-pass contract shape

- `json_decode()` parses one full JSON document into `mixed_t`
- decoded scalars stay inline in `mixed_t`
- decoded arrays and objects both become `dynamic_t`
- packed `hash_t` encodes as a JSON array
- non-packed `hash_t` encodes as a JSON object
- object-vs-array differentiation follows the same internal model as hand-written Prism++ / Simple C++ code
- invalid JSON throws a runtime error with a byte position
- `json_encode()` rejects weak tables and non-finite floats in this pass

## Implemented JSON functions

- `json_decode`
- `json_encode`

## More detailed contracts

For one-file-per-builtin contracts, see `specs/builtins/json/`.
