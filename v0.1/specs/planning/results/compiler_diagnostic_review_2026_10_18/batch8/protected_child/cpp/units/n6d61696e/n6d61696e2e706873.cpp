#include "types/n43.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "utility"
scpp::int_t<> record_C::method_f(::scpp::shared_p<record_C> argument_0) { return record_C::implementation_C__method_f(std::move(argument_0)); }
scpp::int_t<> record_C::implementation_C__method_f(::scpp::shared_p<record_C> local_c)
{
	return (local_c)->field_n;
}

