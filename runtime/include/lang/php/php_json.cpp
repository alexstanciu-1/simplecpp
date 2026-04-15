#include "lang/php/php_json.hpp"

#include "modules/json/json.hpp"

namespace scpp::php {

string_t json_encode(const mixed_t &value) {
	return scpp::json::json_encode(value);
}

mixed_t json_decode(const string_t &json) {
	return scpp::json::json_decode(json);
}

} // namespace scpp::php
