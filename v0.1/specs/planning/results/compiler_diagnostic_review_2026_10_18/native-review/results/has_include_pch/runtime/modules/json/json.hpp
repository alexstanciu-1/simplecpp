#pragma once

#include "modules/json/document.hpp"
#include "modules/json/pull_reader.hpp"

#include "scpp/dynamic_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/result.hpp"
#include "scpp/json/writer.hpp"
#include "scpp/support/dynamic_value.hpp"

namespace scpp::json {

// v0.2 default: same-family tagged dynamic, including scalar roots.
// The tagged implementation is internal until the coordinated public-type switch.
// Malformed input is an ordinary checked parse failure; see specs/builtins/json/json_decode.md.
// Distinct out-of-line symbol prevents linking the old result<mixed_t> ABI.
[[nodiscard]] result<detail::dynamic_value> decode_dynamic(const string_t &json);
[[nodiscard]] result<mixed_t> decode_mixed(const string_t &json);

[[nodiscard]] inline result<detail::dynamic_value> decode(const string_t &json) {
	return decode_dynamic(json);
}

[[nodiscard]] inline result<detail::dynamic_value> json_decode(const string_t &json) {
	return decode(json);
}

[[nodiscard]] inline result<mixed_t> json_decode_mixed(const string_t &json) {
	return decode_mixed(json);
}

// Encodes one runtime value into JSON text.
// How: packed hash_t values become JSON arrays and non-packed hash_t / dynamic_t values become
// JSON objects.
// Unsupported values return an error without exposing partial output.
[[nodiscard]] result<string_t> encode(const mixed_t &value);
[[nodiscard]] result<string_t> encode(const detail::dynamic_value &value);

template <typename T>
	requires(writer_detail::is_supported_v<T> && !std::is_same_v<detail::remove_cvref_t<T>, mixed_t>)
[[nodiscard]] inline result<string_t> encode(const T &value) {
	return writer_detail::encode(value);
}

[[nodiscard]] inline result<string_t> json_encode(const mixed_t &value) {
	return encode(value);
}

[[nodiscard]] inline result<string_t> json_encode(const detail::dynamic_value &value) {
	return encode(value);
}

template <typename T>
	requires(writer_detail::is_supported_v<T> && !std::is_same_v<detail::remove_cvref_t<T>, mixed_t>)
[[nodiscard]] inline result<string_t> json_encode(const T &value) {
	return encode(value);
}

} // namespace scpp::json
