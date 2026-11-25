#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return static_cast<int>((([&]() -> scpp::int_t<> { if (scpp::php::condition_truthy(([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return (static_cast<scpp::int_t<>>(1LL) + static_cast<scpp::int_t<>>(2LL)); }())).native_value()) { return static_cast<scpp::int_t<>>(7LL); } return static_cast<scpp::int_t<>>(9LL); }())).native_value());
	return 0;
}
