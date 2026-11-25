#include "types/n43.hpp"
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "utility"
#include "types/n49.hpp"
scpp::unique_p<scpp::int_t<>> record_I::method_f(scpp::unique_p<scpp::int_t<>> argument_0) { return dispatch_I__method_f(std::move(argument_0)); }
scpp::unique_p<scpp::int_t<>> record_C::dispatch_C__method_f(scpp::unique_p<scpp::int_t<>> argument_0) { return record_C::implementation_C__method_f(std::move(argument_0)); }
scpp::unique_p<scpp::int_t<>> record_C::method_f(scpp::unique_p<scpp::int_t<>> argument_0) { return dispatch_C__method_f(std::move(argument_0)); }
scpp::unique_p<scpp::int_t<>> record_C::dispatch_I__method_f(scpp::unique_p<scpp::int_t<>> argument_0) { return dispatch_C__method_f(std::move(argument_0)); }
scpp::unique_p<scpp::int_t<>> record_C::implementation_C__method_f(scpp::unique_p<scpp::int_t<>> local_p)
{
	return scpp::unique_p<scpp::int_t<>>(std::move(local_p));
}

