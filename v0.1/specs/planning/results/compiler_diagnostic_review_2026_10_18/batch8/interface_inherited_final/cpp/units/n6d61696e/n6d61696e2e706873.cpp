#include "types/n42.hpp"
#include "scpp/int_t.hpp"
#include "types/n49.hpp"
#include "types/n43.hpp"
scpp::int_t<> record_I::method_f() { return dispatch_I__method_f(); }
scpp::int_t<> record_B::method_f() { return record_B::implementation_B__method_f(); }
scpp::int_t<> record_B::implementation_B__method_f()
{
	return static_cast<scpp::int_t<>>(1LL);
}

scpp::int_t<> record_C::dispatch_C__method_f() { return record_B::implementation_B__method_f(); }
scpp::int_t<> record_C::method_f() { return dispatch_C__method_f(); }
scpp::int_t<> record_C::dispatch_I__method_f() { return dispatch_C__method_f(); }
