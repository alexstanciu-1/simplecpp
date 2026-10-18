#include "types/n43.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/memory.hpp"
record_C::record_C(record_C::scpp_construction_key)
{
}

::scpp::shared_p<record_C> record_C::implementation_make_for_type_C()
{
	return ([&]() -> ::scpp::shared_p<record_C> {
	return ::scpp::shared<record_C>(record_C::scpp_constructor_key());
}());
}

