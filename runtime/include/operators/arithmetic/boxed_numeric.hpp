#pragma once

#include "scpp/generated/operators.hpp"
#include "scpp/support/dynamic_value.hpp"
#include <functional>

namespace scpp::detail {

// Runtime kind selection only. Primitive wrappers own arithmetic and result types.
// The boxed family remains an explicit template parameter, never a mixed bridge.
template <typename Box, typename Operation>
Box boxed_numeric_binary(const Box &left, const Box &right, Operation operation, const char *name) {
	using kind = typename Box::kind_t;
	auto apply = [&](auto lhs, auto rhs) -> Box {
		return Box{operation(lhs, rhs)};
	};
	if (left.kind() == kind::int_v && right.kind() == kind::int_v) return apply(left.get_int(), right.get_int());
	if (left.kind() == kind::int_v && right.kind() == kind::float_v) return apply(left.get_int(), right.get_float());
	if (left.kind() == kind::float_v && right.kind() == kind::int_v) return apply(left.get_float(), right.get_int());
	if (left.kind() == kind::float_v && right.kind() == kind::float_v) return apply(left.get_float(), right.get_float());
	throw std::runtime_error(std::string(name) + ": expected int or float payloads");
}

inline dynamic_value operator+(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_binary(left, right, std::plus<>{}, "+");
}
inline dynamic_value operator-(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_binary(left, right, std::minus<>{}, "-");
}
inline dynamic_value operator*(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_binary(left, right, std::multiplies<>{}, "*");
}
inline dynamic_value operator/(const dynamic_value &left, const dynamic_value &right) {
	return boxed_numeric_binary(left, right, std::divides<>{}, "/");
}
inline dynamic_value operator%(const dynamic_value &left, const dynamic_value &right) {
	using kind = dynamic_value::kind_t;
	if (left.kind() != kind::int_v || right.kind() != kind::int_v)
		throw std::runtime_error("dynamic modulo requires int payloads");
	return dynamic_value{left.get_int() % right.get_int()};
}

inline dynamic_value operator+(const dynamic_value &value) {
	if (value.kind() == dynamic_value::kind_t::int_v) return dynamic_value{+value.get_int()};
	if (value.kind() == dynamic_value::kind_t::float_v) return dynamic_value{+value.get_float()};
	throw std::runtime_error("dynamic unary plus requires numeric payload");
}
inline dynamic_value operator-(const dynamic_value &value) {
	if (value.kind() == dynamic_value::kind_t::int_v) return dynamic_value{-value.get_int()};
	if (value.kind() == dynamic_value::kind_t::float_v) return dynamic_value{-value.get_float()};
	throw std::runtime_error("dynamic unary minus requires numeric payload");
}

} // namespace scpp::detail
