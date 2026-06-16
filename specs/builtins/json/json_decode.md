# `json_decode`
Doc Status: normative
## Current contract

Source-facing PHP++ / PHS contract:

`json_decode(string $json) -> dynamic`

Runtime carrier:

`scpp::php::json_decode(const string_t &json) -> mixed_t`

## Behavior

Parses one complete JSON document and returns dynamic JSON data at the source-language boundary.

The runtime implementation currently carries that value through `mixed_t`, with arrays/objects represented by dynamic/shared table forms.

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
- malformed input throws `std::runtime_error` with a byte-position message
- object key normalization is intentionally the same as ordinary `hash_t` insertion in PHP-target mode

## Registration note

- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.
