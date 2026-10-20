#include "lang/php/support/php_string.hpp"
#include "scpp/vector_t.hpp"

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

extern "C" std::int64_t scpp_v2_string_compare(void *left, void *right) {
	const auto *lhs = static_cast<const scpp::string_t *>(left);
	const auto *rhs = static_cast<const scpp::string_t *>(right);
	if (lhs == nullptr && rhs == nullptr) {
		return 0;
	}
	if (lhs == nullptr) {
		return -1;
	}
	if (rhs == nullptr) {
		return 1;
	}

	const int result = lhs->native_value().compare(rhs->native_value());
	if (result < 0) {
		return -1;
	}
	if (result > 0) {
		return 1;
	}
	return 0;
}

extern "C" void scpp_v2_string_release(void *value) {
	delete static_cast<scpp::string_t *>(value);
}

extern "C" void *scpp_v2_vector_i32_new() {
	return new scpp::vector_t<std::int32_t>();
}

extern "C" void scpp_v2_vector_i32_append(void *value, std::int32_t item) {
	if (value == nullptr) {
		return;
	}

	static_cast<scpp::vector_t<std::int32_t> *>(value)->append(item);
}

extern "C" std::int32_t scpp_v2_vector_i32_at(void *value, std::int64_t index) {
	if (value == nullptr || index < 0) {
		return 0;
	}

	return static_cast<scpp::vector_t<std::int32_t> *>(value)->at(static_cast<std::size_t>(index));
}

extern "C" void scpp_v2_vector_i32_release(void *value) {
	delete static_cast<scpp::vector_t<std::int32_t> *>(value);
}
