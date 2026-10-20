#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
scpp::nullable<scpp::int_t<>> function_some();
scpp::nullable<scpp::int_t<>> function_some()
{
	return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(5LL)};
}


int main()
{
	scpp::int_t<> local_x = scpp::int_t<>((([&]() -> scpp::nullable<scpp::int_t<>> {
	return function_some();
}())).require_value("nullable required-value extraction"));
	return static_cast<int>((local_x).native_value());
	return 0;
}
