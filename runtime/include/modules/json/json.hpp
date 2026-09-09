#pragma once

#include "scpp/dynamic_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/result.hpp"
#include "scpp/json/to_json.hpp"

namespace scpp::json {

// Decodes one JSON document directly into the current Prism++ runtime value model.
// Scalars stay inline in mixed_t; arrays and objects use dynamic_t with shared table storage.
// Malformed input is an ordinary checked parse failure; see specs/builtins/json/json_decode.md.
[[nodiscard]] result<mixed_t> decode(const string_t &json);

[[nodiscard]] inline result<mixed_t> json_decode(const string_t &json) {
	return decode(json);
}

// Encodes one runtime value into JSON text.
// How: packed hash_t values become JSON arrays and non-packed hash_t / dynamic_t values become
// JSON objects.
// Unsupported values return an error without exposing partial output.
[[nodiscard]] result<string_t> encode(const mixed_t &value);

template <typename T>
	requires(to_json_detail::is_supported_v<T> && !std::is_same_v<detail::remove_cvref_t<T>, mixed_t>)
[[nodiscard]] inline result<string_t> encode(const T &value) {
	return encode(to_json(value));
}

[[nodiscard]] inline result<string_t> json_encode(const mixed_t &value) {
	return encode(value);
}

template <typename T>
	requires(to_json_detail::is_supported_v<T> && !std::is_same_v<detail::remove_cvref_t<T>, mixed_t>)
[[nodiscard]] inline result<string_t> json_encode(const T &value) {
	return encode(value);
}

} // namespace scpp::json
