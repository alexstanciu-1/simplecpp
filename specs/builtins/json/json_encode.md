# `json_encode`
Doc Status: normative
## Current contract

- `scpp::php::json_encode(const mixed_t &value) -> string_t`
- `scpp::php::json_encode(const hash_t<mixed_t> &value) -> string_t`
- `scpp::php::json_encode(const shared_p<hash_t<mixed_t>> &value) -> string_t`
- `scpp::json::to_json<T>(const T &value) -> mixed_t` for supported typed JSON value shapes
- `scpp::json::json_encode(const T &value) -> string_t` for supported typed JSON value shapes

## Behavior

Encodes a runtime value into JSON text.

### Value mapping

- inline scalar `mixed_t` values map to ordinary JSON scalars
- packed `hash_t<mixed_t>` values encode as JSON arrays
- non-packed `hash_t<mixed_t>` values encode as JSON objects
- non-packed integer keys are emitted as JSON string keys
- supported typed scalar, nullable, vector, and string/int-keyed hash values first normalize through `scpp::json::to_json<T>` and then use the same JSON writer path as `mixed_t`
- typed `vector_t<T>` values encode as JSON arrays
- typed `hash_t<T, string_t>` and `hash_t<T, int_t>` values encode as JSON objects

## Notes

- strings escape JSON control characters and quotes
- UTF-8 bytes above ASCII are emitted as-is in this pass
- non-finite `float_t` values throw
- weak tables throw
- `shared_p<hash_t<mixed_t>>{null}` encodes as `null`

## Registration note

- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.
