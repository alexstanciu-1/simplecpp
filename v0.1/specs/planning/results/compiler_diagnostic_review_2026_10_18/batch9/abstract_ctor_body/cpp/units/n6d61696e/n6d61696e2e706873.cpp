#include "types/n43.hpp"
#include "types/n41.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> record_A::method_f() { return dispatch_A__method_f(); }
record_C::record_C(record_A local_a)
{
	([&]() -> scpp::int_t<> {
auto& receiver_0 = local_a;
	return receiver_0.method_f();
}());
}

