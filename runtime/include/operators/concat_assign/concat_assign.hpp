#pragma once

#include "lang/php/support/php_common.hpp"

namespace scpp {

// Implements concatenation assignment for wrapped strings.
// How: the helper mutates the left-hand side in place through string_t::append and returns the updated wrapper by reference.
inline string_t &concat_assign(string_t &left, const string_t &right) {
	left.append(right);
	return left;
}

} // namespace scpp
