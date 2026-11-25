#include "callables/n66.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> function_f(scpp::bool_t local_x)
{
if ((local_x).native_value()) {
	return static_cast<scpp::int_t<>>(1LL);
}
	return static_cast<scpp::int_t<>>(0LL);
}

