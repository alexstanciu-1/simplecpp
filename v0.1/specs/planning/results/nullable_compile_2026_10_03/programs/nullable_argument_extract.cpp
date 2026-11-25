#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
scpp::int_t<> function_read(scpp::int_t<> local_x);
scpp::int_t<> function_read(scpp::int_t<> local_x)
{
	return local_x;
}


int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(8LL)};
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
	return function_read(argument_0);
}())).native_value());
	return 0;
}
