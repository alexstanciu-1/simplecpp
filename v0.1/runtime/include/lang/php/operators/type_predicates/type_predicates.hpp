#pragma once

#include "lang/php/support/php_common.hpp"
#include "scpp/support/mixed_t.hpp"

namespace scpp::php {

template <typename T>
inline bool_t is_bool(const T &) {
	return bool_t{false};
}

inline bool_t is_bool(const bool_t &) {
	return bool_t{true};
}

inline bool_t is_bool(const mixed_t &value) {
	return value.is_bool();
}

template <typename T>
inline bool_t is_int(const T &) {
	return bool_t{false};
}

template <typename Rep>
inline bool_t is_int(const int_t<Rep> &) {
	return bool_t{true};
}

inline bool_t is_int(const mixed_t &value) {
	return value.is_int();
}

template <typename T>
inline bool_t is_float(const T &) {
	return bool_t{false};
}

inline bool_t is_float(const float_t &) {
	return bool_t{true};
}

inline bool_t is_float(const mixed_t &value) {
	return value.is_float();
}

} // namespace scpp::php
