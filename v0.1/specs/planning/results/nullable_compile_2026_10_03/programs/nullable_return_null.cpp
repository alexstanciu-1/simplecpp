#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::nullable<scpp::int_t<>> function_none();
scpp::nullable<scpp::int_t<>> function_none()
{
	return scpp::nullable<scpp::int_t<>>{scpp::null};
}


int main()
{
	return static_cast<int>(((!(([&]() -> scpp::nullable<scpp::int_t<>> {
	return function_none();
}())).has_value())).native_value());
	return 0;
}
