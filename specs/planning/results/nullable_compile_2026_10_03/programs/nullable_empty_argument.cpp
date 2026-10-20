#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
scpp::int_t<> function_read(scpp::int_t<> local_x);
scpp::int_t<> function_fail();
scpp::int_t<> function_read(scpp::int_t<> local_x)
{
	return local_x;
}

scpp::int_t<> function_fail()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return ([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
	return function_read(argument_0);
}());
}


int main()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
