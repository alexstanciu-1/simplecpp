#include "scpp/bool_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::bool_t> local_x = scpp::nullable<scpp::bool_t>{static_cast<scpp::bool_t>(false)};
	auto local_y = ([&]() -> scpp::nullable<scpp::bool_t> { scpp::nullable<scpp::bool_t> conditional_0 = local_x; if (scpp::php::condition_truthy(conditional_0).native_value()) { return conditional_0; } return scpp::nullable<scpp::bool_t>{static_cast<scpp::bool_t>(true)}; }());
	return static_cast<int>((([&]() -> scpp::bool_t { scpp::nullable<scpp::bool_t> coalesce_1 = local_y; if (coalesce_1.has_value().native_value()) { return scpp::bool_t((coalesce_1).require_value("nullable required-value extraction")); } return static_cast<scpp::bool_t>(false); }())).native_value());
	return 0;
}
