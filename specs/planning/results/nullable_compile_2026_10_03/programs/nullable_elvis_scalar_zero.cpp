#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_y = ([&]() -> scpp::nullable<scpp::int_t<>> { scpp::int_t<> conditional_0 = static_cast<scpp::int_t<>>(0LL); if (scpp::php::condition_truthy(conditional_0).native_value()) { return scpp::nullable<scpp::int_t<>>{conditional_0}; } return local_x; }());
	return static_cast<int>(((!(local_y).has_value())).native_value());
	return 0;
}
