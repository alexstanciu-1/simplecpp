#include "types/n43.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> record_C::method_f() { return record_C::implementation_C__method_f(); }
scpp::int_t<> record_C::implementation_C__method_f()
{
	auto local_g = [this]() mutable -> scpp::int_t<> {
	return (this)->field_n;
};
	auto local_h = local_g;
	local_h = local_g;
	return ([&]() -> scpp::int_t<> {
auto callee_0 = local_h;
	return callee_0();
}());
}

