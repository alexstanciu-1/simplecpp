#pragma once

#include "lang/php/support/php_common.hpp"
#include "scpp/cast.hpp"

namespace scpp {

// Implements concatenation assignment for wrapped strings.
// How: the helper mutates the left-hand side in place through string_t::append and returns the updated wrapper by reference.
inline string_t &concat_assign(string_t &left, const string_t &right) {
	left.append(right);
	return left;
}

inline detail::dynamic_value &concat_assign(detail::dynamic_value &left, const string_t &right) {
	auto text = cast<string_t>(left);
	text.append(right);
	left = detail::dynamic_value{text};
	return left;
}

} // namespace scpp
