#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
scpp::int_t<> function_fail();
scpp::int_t<> function_fail()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return ([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return (static_cast<scpp::int_t<>>(1LL) / static_cast<scpp::int_t<>>(0LL)); }());
}


int main()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
