# `json_encode`
Doc Status: normative
## Current contract

- Source-facing PHP++ / PHS: `json_encode(mixed $value) -> result<string>`
- Shared writer: `scpp::json::encode(const mixed_t &value) -> result<string_t>`
- `scpp::json::json_encode` and `scpp::php::json_encode` forward to the checked writer
- `scpp::json::to_json<T>(const T &value) -> mixed_t` for supported typed JSON value shapes
- `scpp::json::encode(const T &value)` and `scpp::json::json_encode(const T &value)` return `result<string_t>` for supported typed JSON value shapes

Both strict and legacy PHP++ profiles expose the checked result contract.

## Behavior

Encodes a runtime value into JSON text.

### Checked failure boundary

Non-finite floats, weak tables, and object keys that cannot be represented as strings or integers return an `error`, including when encountered inside a container. These are ordinary encoding failures, not runtime type failures or source-language exceptions.

```php
$text string = "";
$err error;
if (take($text, $err, json_encode($value))) {
	echo $text, "\n";
} else {
	echo $err->get_message(), "\n";
}
```

On failure, `take` returns `false`, assigns `$err`, and leaves `$text` unchanged. No partial JSON text is returned. Subsequent encodes in the same process are independent and can succeed. `null` and `false` encode successfully as the strings `"null"` and `"false"`.

The error message names the rejected value category. Line/file fields retain their defaults. Allocation failures and unrelated runtime exceptions retain their own contracts. Required-value extraction from an error result follows the existing wrapper failure contract; `take` is the normal recovery boundary.

This replaces the former direct string return. Unwrap before printing or passing encoded text to another helper. Rebuild runtime artifacts when adopting this change (`scpp run --force` for a project, or `scpp runtime-build --force` for the shared cache). The out-of-line `encode` symbol prevents an older runtime from satisfying the former `json_encode` ABI with an incompatible return value.

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
- non-finite `float_t` values return an error
- weak tables return an error
- `shared_p<hash_t<mixed_t>>{null}` encodes as `null`

## Registration note

- Registered in both PHP profile registries; strict lowers to `json::encode`, legacy to the PHP adapter.
