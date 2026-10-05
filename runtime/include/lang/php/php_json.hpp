#pragma once

#include "modules/json/json.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

[[nodiscard]] inline result<string_t> json_encode(const mixed_t &value) {
	return scpp::json::json_encode(value);
}

[[nodiscard]] inline result<string_t> json_encode(const ::scpp::detail::dynamic_value &value) {
	return scpp::json::encode(value);
}

[[nodiscard]] inline result<::scpp::detail::dynamic_value> json_decode(const string_t &json) {
	return scpp::json::json_decode(json);
}

[[nodiscard]] inline result<mixed_t> json_decode_mixed(const string_t &json) {
	return scpp::json::decode_mixed(json);
}

} // namespace scpp::php
