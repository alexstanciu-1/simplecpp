#pragma once

#include "scpp/generated/operators.hpp"
#include "operators/conditional/condition_truthiness.hpp"
#include <cerrno>
#include <cctype>
#include <cmath>
#include <cstdlib>

namespace scpp::detail {

template <typename Box>
bool is_boxed_scalar(const Box &value) {
	using kind = typename Box::kind_t;
	return value.kind() == kind::null_v || value.kind() == kind::bool_v
		|| value.kind() == kind::int_v || value.kind() == kind::float_v || value.kind() == kind::string_v;
}

// Preserve the established numeric-string equality parser; this is not a cast.
inline bool numeric_string_equal(double number, const string_t &text) {
	const auto &value = text.native_value();
	const char *begin = value.c_str();
	char *end = nullptr;
	errno = 0;
	const double parsed = std::strtod(begin, &end);
	if (end == begin) return false;
	while (*end != '\0') {
		if (std::isspace(static_cast<unsigned char>(*end)) == 0) return false;
		++end;
	}
	return errno != ERANGE && std::isfinite(parsed) && number == parsed;
}

template <typename Box>
bool_t boxed_scalar_equal(const Box &left, const Box &right) {
	using kind = typename Box::kind_t;
	if (!is_boxed_scalar(left) || !is_boxed_scalar(right))
		throw std::runtime_error("scalar equality requires scalar payloads");
	if (left.kind() == kind::null_v || right.kind() == kind::null_v) {
		const auto &other = left.kind() == kind::null_v ? right : left;
		if (other.kind() == kind::string_v) return bool_t{other.get_string().native_value().empty()};
		return bool_t{!condition_truthy(other).native_value()};
	}
	if (left.kind() == kind::bool_v || right.kind() == kind::bool_v)
		return bool_t{condition_truthy(left).native_value() == condition_truthy(right).native_value()};
	if (left.kind() == kind::string_v && right.kind() == kind::string_v)
		return left.get_string() == right.get_string();
	if (left.kind() == kind::string_v || right.kind() == kind::string_v) {
		const auto &number = left.kind() == kind::string_v ? right : left;
		const auto &text = left.kind() == kind::string_v ? left : right;
		const double numeric = number.kind() == kind::int_v
			? static_cast<double>(number.get_int().native_value()) : number.get_float().native_value();
		return bool_t{numeric_string_equal(numeric, text.get_string())};
	}
	if (left.kind() == kind::int_v && right.kind() == kind::int_v) return left.get_int() == right.get_int();
	if (left.kind() == kind::int_v) return left.get_int() == right.get_float();
	if (right.kind() == kind::int_v) return left.get_float() == right.get_int();
	return left.get_float() == right.get_float();
}

inline bool_t operator==(const dynamic_value &left, const dynamic_value &right) {
	const auto *left_table = left.table_if();
	const auto *right_table = right.table_if();
	if (left_table || right_table) return bool_t{left_table && left_table == right_table};
	return boxed_scalar_equal(left, right);
}

inline bool_t operator!=(const dynamic_value &left, const dynamic_value &right) {
	return bool_t{!(left == right).native_value()};
}

} // namespace scpp::detail
