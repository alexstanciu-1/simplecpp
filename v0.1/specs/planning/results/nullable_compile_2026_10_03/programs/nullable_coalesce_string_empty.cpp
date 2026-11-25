#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::string_t("")};
	auto local_y = ([&]() -> scpp::string_t { scpp::nullable<scpp::string_t> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::string_t((coalesce_0).require_value("nullable required-value extraction")); } return scpp::string_t("9"); }());
	return static_cast<int>((([&]() -> scpp::int_t<> { if (scpp::php::condition_truthy(local_y).native_value()) { return static_cast<scpp::int_t<>>(9LL); } return static_cast<scpp::int_t<>>(1LL); }())).native_value());
	return 0;
}
