#include "callables/n66.hpp"
#include "scpp/int_t.hpp"
scpp::int_t<> function_f(scpp::int_t<> local_n)
{
switch ((local_n).native_value()) {
default:
{
	return static_cast<scpp::int_t<>>(1LL);
}
case 1LL:
{
	return static_cast<scpp::int_t<>>(2LL);
}
}
}

