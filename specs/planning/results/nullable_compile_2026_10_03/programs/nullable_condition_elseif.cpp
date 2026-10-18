#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
if ((static_cast<scpp::bool_t>(false)).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(9LL)).native_value());
}
else if (scpp::php::condition_truthy(local_x).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(3LL)).native_value());
}
	return static_cast<int>((static_cast<scpp::int_t<>>(9LL)).native_value());
	return 0;
}
