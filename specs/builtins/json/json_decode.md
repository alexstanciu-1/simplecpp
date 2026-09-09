# `json_decode`
Doc Status: normative
## Current contract

Source-facing PHP++ / PHS contract:

`json_decode(string $json) -> result<mixed>`

Runtime carrier:

`scpp::json::decode(const string_t &json) -> result<mixed_t>`

`scpp::json::json_decode` and `scpp::php::json_decode` forward to the same checked decoder.
Both strict and legacy PHP++ profiles expose this result contract.

## Behavior

Parses one complete JSON document and returns either its value or an `error`.

The successful value uses `mixed_t`, with arrays/objects represented by dynamic/shared table forms. The source return type is `result<mixed>` because a valid JSON document may also be a scalar, `null`, or `false`.

### Checked failure boundary

Malformed input is an ordinary parse failure, not a runtime type failure or a source-language exception. It returns the error branch with a message of the form `json error at byte N: reason`; `N` is a zero-based byte offset. No partial document is returned.

Use `take` to handle that branch:

```php
$data mixed;
$err error;
if (!take($data, $err, json_decode($text))) {
	echo $err->get_message(), "\n";
}
```

Use `$data` only after successful extraction.

On parse failure, `take` returns `false`, assigns `$err`, and leaves `$data` unchanged. The process can decode repaired input on its next request. Valid JSON `null` and `false` are successful values, so `take` returns `true` for both.

`take` consumes the decoder's result; it does not suppress unrelated exceptions raised while evaluating arguments. Allocation failures and runtime failures outside JSON parsing retain their existing contracts. Required-value extraction from an error result remains wrapper misuse and follows the existing result contract. Typed field conversions after successful decoding also retain their existing cast contract.

This replaces the former direct dynamic return. Callers should unwrap with `take` before inspecting or encoding decoded data.

Rebuild existing runtime artifacts when adopting this contract (`scpp run --force` forces a project rebuild, or `scpp runtime-build --force` refreshes the shared cache). The checked decoder uses the out-of-line `decode` symbol so an older runtime cannot silently satisfy the former `json_decode` ABI with an incompatible return value.

### Value mapping

- `null` -> `mixed_t(null_t{})`
- `true` / `false` -> `mixed_t(bool_t(...))`
- integer numbers -> `mixed_t(int_t(...))`
- fractional/exponent numbers -> `mixed_t(float_t(...))`
- strings -> `mixed_t(string_t(...))`
- arrays -> `mixed_t(dynamic_t)` backed by shared packed storage
- objects -> `mixed_t(dynamic_t)` backed by shared associative storage

## Notes

- the parser consumes the whole document; trailing non-whitespace is an error
- unicode escapes are decoded, including surrogate pairs
- malformed input returns an `error` with a byte-position message
- object key normalization is intentionally the same as ordinary `hash_t` insertion in PHP-target mode

## Registration note

- Registered in both PHP profile registries; strict lowers to `json::decode`, legacy to the PHP adapter.
