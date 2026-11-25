#include "types/n43.hpp"
#include "scpp/int_t.hpp"
#include "types/n41.hpp"
scpp::int_t<> record_A::method_f() { return dispatch_A__method_f(); }
scpp::int_t<> record_C::dispatch_C__method_f() { return record_C::implementation_C__method_f(); }
scpp::int_t<> record_C::method_f() { return dispatch_C__method_f(); }
scpp::int_t<> record_C::dispatch_A__method_f() { return dispatch_C__method_f(); }
scpp::int_t<> record_C::implementation_C__method_f()
{
	return static_cast<scpp::int_t<>>(1LL);
}

