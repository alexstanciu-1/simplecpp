#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
scpp::nullable<scpp::int_t<>> function_carry(scpp::nullable<scpp::int_t<>> local_x);
scpp::int_t<> function_read(scpp::nullable<scpp::int_t<>> local_x);
scpp::nullable<scpp::int_t<>> function_carry(scpp::nullable<scpp::int_t<>> local_x)
{
	return local_x;
}

scpp::int_t<> function_read(scpp::nullable<scpp::int_t<>> local_x)
{
	return scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
}


int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::nullable<scpp::int_t<>> argument_1 = ([&]() -> scpp::nullable<scpp::int_t<>> {
	scpp::nullable<scpp::int_t<>> argument_0 = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(6LL)};
	return function_carry(argument_0);
}());
	return function_read(argument_1);
}())).native_value());
	return 0;
}
