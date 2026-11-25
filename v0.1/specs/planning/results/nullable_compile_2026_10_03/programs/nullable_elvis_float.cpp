#include "scpp/float_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::float_t> local_x = scpp::nullable<scpp::float_t>{static_cast<scpp::float_t>(0.0)};
	auto local_y = ([&]() -> scpp::nullable<scpp::float_t> { scpp::nullable<scpp::float_t> conditional_0 = local_x; if (scpp::php::condition_truthy(conditional_0).native_value()) { return conditional_0; } return scpp::nullable<scpp::float_t>{static_cast<scpp::float_t>(3.5)}; }());
	return static_cast<int>((([&]() -> scpp::float_t { scpp::nullable<scpp::float_t> coalesce_1 = local_y; if (coalesce_1.has_value().native_value()) { return scpp::float_t((coalesce_1).require_value("nullable required-value extraction")); } return static_cast<scpp::float_t>(9.5); }())).native_value());
	return 0;
}
