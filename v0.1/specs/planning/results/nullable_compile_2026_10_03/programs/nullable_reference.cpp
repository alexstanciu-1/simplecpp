#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"
void function_clear(scpp::nullable<scpp::int_t<>>& local_x);
void function_clear(scpp::nullable<scpp::int_t<>>& local_x)
{
	local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
}


int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
	([&]() -> void {
	scpp::nullable<scpp::int_t<>>& argument_0 = local_x;
	return function_clear(argument_0);
}());
	return static_cast<int>(((!(local_x).has_value())).native_value());
	return 0;
}
