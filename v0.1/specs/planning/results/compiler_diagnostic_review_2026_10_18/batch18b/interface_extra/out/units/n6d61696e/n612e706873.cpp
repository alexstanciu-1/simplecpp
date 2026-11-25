#include "types/n41.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> record_A::dispatch_A__method_f() { return record_A::implementation_A__method_f(); }
scpp::int_t<> record_A::method_f() { return dispatch_A__method_f(); }
scpp::int_t<> record_A::dispatch_I__method_f() { return dispatch_A__method_f(); }
scpp::int_t<> record_A::implementation_A__method_f()
{
	return static_cast<scpp::int_t<>>(1LL);
}

