# `json_decode`
Doc Status: normative
## v0.2 family-specific decoding — 2026-10-05

### Unique object names — 2026-10-06

All Simple C++ JSON parsing requires unique member names within each object.
This includes default dynamic decoding, explicit mixed decoding, language adapters,
and the JSON document API when integrated. There is no last-value-wins option.
This is an intentional restriction beyond JSON syntax, not a claim that the JSON
standard forbids duplicates. It supersedes earlier duplicate-accepting contracts.

- Compare decoded key strings exactly, case-sensitively, without Unicode normalization.
  `"name"` and `"\u006eame"` are duplicates; `"Name"` and `"name"` are distinct.
- Check before runtime table-key normalization. `"0"` and `"00"` remain distinct
  JSON names. The same name in different nested/sibling objects is allowed.
- A duplicate is a checked parse failure, with the repeated key identified and
  a zero-based byte offset at its opening quote. Return no partial document/value;
  failed `take` leaves the destination unchanged. Repair and retry remain supported.
- The PHP portability schema adapter throws its existing JSON exception before
  publishing a view; it must not inherit PHP's silent overwrite behavior.

Implemented for both runtime value decoders and the PHP schema adapter. The native
document API remains a separate integration prerequisite; its historical candidate
must be adapted and revalidated against this rule before adoption.

The approved v0.2 surface replaces the v0.1 contract below:

- `json_decode(string $json) -> result<dynamic>` is the default, including scalar roots.
- `json_decode_mixed(string $json) -> result<mixed>` explicitly requests value/COW data.
- Runtime entrypoints are `scpp::json::decode` and `scpp::json::decode_mixed`.
- One parser constructs the selected family directly. Nested containers and leaves
  retain that family; there is no cross-family intermediate or runtime mode flag.
- Existing checked parse failures, numeric/string parsing, key normalization and
  full-document consumption remain unchanged. Valid null and false are successes.
- Container shape remains hash-derived; this does not preserve empty `{}` versus `[]`.

Migration status: runtime decoding and public `dynamic_t<>` use the new tagged
dynamic storage. The experimental `compiler/my-try` strict source path registers
both decoder signatures through ordinary runtime-function lookup. This does not
claim complete library migration or typed `from_json` source registration.
The default-family change is intentional: follow the
[v0.2 repair rules](../../dynamic_types.md#v02-migration-and-repair-rules), not old
mixed-return test expectations. Select `json_decode_mixed` when mixed is required.
The out-of-line dynamic decoder has a new symbol (`decode_dynamic`) to prevent
linking the former mixed-return ABI. Rebuild runtime artifacts and generated code.

### Typed runtime extraction

`scpp::json::from_json<T>(value)` accepts either decoded family and uses the same
checked scalar/nullable/vector/string-keyed-hash extraction path. Scalar type
checks remain exact, with the existing integer-to-float widening. Errors identify
the failing JSON path; partially built destinations are not published.

Same-family dynamic extraction shares container identity; mixed extraction retains
independent value behavior. Explicit scalar/null family crossings are allowed;
container-family crossings reject, including inside typed containers. The former
mixed-box-to-dynamic-handle extraction is removed, not retained as compatibility.

`from_json<T>(text)` follows default dynamic decoding. To extract a mixed container,
call `from_json<T>(decode_mixed(text).value())` after handling the decode result as
appropriate. The typed helper does not silently select a different decoder based
on the requested type. It still throws on typed conversion failure; it does not
introduce another checked-result API.

## Historical v0.1 contract

The following describes the superseded v0.1 surface, not the v0.2 default.

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
