#include "callables/n66.hpp"
#include "scpp/int_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/result.hpp"
#include "operators/count/count.hpp"
scpp::int_t<> function_f(scpp::result<scpp::vector_t<scpp::int_t<>>> local_r)
{
	scpp::vector_t<scpp::int_t<>> local_v = scpp::vector_t<scpp::int_t<>>((local_r).require_value("Result required-value extraction"));
	return scpp::count(static_cast<const scpp::vector_t<scpp::int_t<>>&>(local_v));
}

