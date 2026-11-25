#include "callables/n66.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
void function_f(scpp::int_t<>& local_x)
{
	static_cast<scpp::int_t<>>(--local_x);
}

