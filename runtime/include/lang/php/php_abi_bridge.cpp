#include "lang/php/support/php_string.hpp"

#include <cstddef>
#include <cstdint>
#include <string>

extern "C" void *scpp_v2_string_from_cstr(const char *bytes, std::int64_t byte_count) {
	if (bytes == nullptr || byte_count <= 0) {
		return new scpp::string_t("");
	}

	return new scpp::string_t(std::string(bytes, static_cast<std::size_t>(byte_count)));
}

extern "C" void *scpp_v2_php_to_string_i64(std::int64_t value) {
	return new scpp::string_t(scpp::php::to_string(scpp::int_t<>(value)));
}

extern "C" void scpp_v2_php_echo_eval_string(void *value) {
	if (value == nullptr) {
		return;
	}

	scpp::php::echo_one(*static_cast<scpp::string_t *>(value));
}

extern "C" void scpp_v2_string_release(void *value) {
	delete static_cast<scpp::string_t *>(value);
}
