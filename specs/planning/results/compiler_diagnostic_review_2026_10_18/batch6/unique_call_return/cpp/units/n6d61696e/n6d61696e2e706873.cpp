#include "callables/n6d616b65.hpp"
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "utility"
#include "callables/n66.hpp"
scpp::unique_p<scpp::int_t<>> function_make()
{
	scpp::unique_p<scpp::int_t<>> local_p;
	return scpp::unique_p<scpp::int_t<>>(std::move(local_p));
}

void function_f(scpp::unique_p<scpp::int_t<>> local_p)
{
}

