# `json_encode`

## Current contract

- `scpp::php::json_encode(const mixed_t &value) -> string_t`
- `scpp::php::json_encode(const hash_t<mixed_t> &value) -> string_t`
- `scpp::php::json_encode(const shared_p<hash_t<mixed_t>> &value) -> string_t`

## Behavior

Encodes a runtime value into JSON text.

### Value mapping

- inline scalar `mixed_t` values map to ordinary JSON scalars
- packed `hash_t<mixed_t>` values encode as JSON arrays
- non-packed `hash_t<mixed_t>` values encode as JSON objects
- non-packed integer keys are emitted as JSON string keys

## Notes

- strings escape JSON control characters and quotes
- UTF-8 bytes above ASCII are emitted as-is in this pass
- non-finite `float_t` values throw
- weak tables throw
- `shared_p<hash_t<mixed_t>>{null}` encodes as `null`

## Registration note

- Registered in `php_generator/specs/php_runtime_symbols.json`.
