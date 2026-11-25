#include "callables/n67.hpp"
#include "scpp/int_t.hpp"
#include "types/n53.hpp"
scpp::int_t<> record_S::method_f() { return dispatch_S__method_f(); }
scpp::int_t<> function_g(record_S& local_s)
{
	return ([&]() -> scpp::int_t<> {
auto& receiver_0 = local_s;
	return receiver_0.method_f();
}());
}

