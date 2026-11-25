#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	scpp::nullable<scpp::int_t<>> local_y = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_z = ([&]() -> scpp::nullable<scpp::int_t<>> { scpp::nullable<scpp::int_t<>> conditional_0 = local_x; if (scpp::php::condition_truthy(conditional_0).native_value()) { return conditional_0; } return local_y; }());
	return static_cast<int>(((!(local_z).has_value())).native_value());
	return 0;
}
