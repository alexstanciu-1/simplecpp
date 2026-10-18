#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::bool_t function_emptyU_value(scpp::nullable<scpp::int_t<>> local_x);
scpp::bool_t function_emptyU_value(scpp::nullable<scpp::int_t<>> local_x)
{
	return (!(local_x).has_value());
}


int main()
{
	return static_cast<int>((([&]() -> scpp::bool_t {
	scpp::nullable<scpp::int_t<>> argument_0 = scpp::nullable<scpp::int_t<>>{scpp::null};
	return function_emptyU_value(argument_0);
}())).native_value());
	return 0;
}
