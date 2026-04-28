#pragma once

#include "modules/json/json.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

[[nodiscard]] inline string_t json_encode(const mixed_t &value) {
	return scpp::json::json_encode(value);
}

[[nodiscard]] inline mixed_t json_decode(const string_t &json) {
	return scpp::json::json_decode(json);
}

} // namespace scpp::php
