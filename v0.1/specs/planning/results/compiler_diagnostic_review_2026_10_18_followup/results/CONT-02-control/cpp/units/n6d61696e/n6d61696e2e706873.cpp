#include "callables/n66.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
scpp::int_t<> function_f(scpp::nullable<scpp::int_t<>> local_r)
{
	return ([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_r; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(1LL); }());
}

