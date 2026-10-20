#include "types/n53.hpp"
#include "scpp/int_t.hpp"
#include "types/n49.hpp"
scpp::int_t<> record_I::method_f() { return dispatch_I__method_f(); }
scpp::int_t<> record_S::dispatch_S__method_f() { return record_S::implementation_S__method_f(); }
scpp::int_t<> record_S::method_f() { return dispatch_S__method_f(); }
scpp::int_t<> record_S::dispatch_I__method_f() { return dispatch_S__method_f(); }
scpp::int_t<> record_S::implementation_S__method_f()
{
	return static_cast<scpp::int_t<>>(1LL);
}

