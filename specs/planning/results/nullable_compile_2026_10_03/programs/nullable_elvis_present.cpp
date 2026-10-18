#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
	auto local_y = ([&]() -> scpp::nullable<scpp::int_t<>> { scpp::nullable<scpp::int_t<>> conditional_0 = local_x; if (scpp::php::condition_truthy(conditional_0).native_value()) { return conditional_0; } return scpp::nullable<scpp::int_t<>>{(static_cast<scpp::int_t<>>(1LL) / static_cast<scpp::int_t<>>(0LL))}; }());
	return static_cast<int>((([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_1 = local_y; if (coalesce_1.has_value().native_value()) { return scpp::int_t<>((coalesce_1).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(9LL); }())).native_value());
	return 0;
}
