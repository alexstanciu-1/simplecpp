#include "scpp/float_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::nullable<scpp::float_t> local_x = scpp::nullable<scpp::float_t>{static_cast<scpp::float_t>(0.0)};
if (scpp::php::condition_truthy(local_x).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(9LL)).native_value());
}
	local_x = scpp::nullable<scpp::float_t>{static_cast<scpp::float_t>(1.5)};
	return static_cast<int>((([&]() -> scpp::int_t<> { if (scpp::php::condition_truthy(local_x).native_value()) { return static_cast<scpp::int_t<>>(3LL); } return static_cast<scpp::int_t<>>(9LL); }())).native_value());
	return 0;
}
