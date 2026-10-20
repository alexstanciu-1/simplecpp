#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::string_t("0")};
	auto local_y = ([&]() -> scpp::nullable<scpp::string_t> { scpp::nullable<scpp::string_t> conditional_0 = local_x; if (scpp::php::condition_truthy(conditional_0).native_value()) { return conditional_0; } return scpp::nullable<scpp::string_t>{scpp::string_t("7")}; }());
	scpp::string_t local_z = scpp::string_t((local_y).require_value("nullable required-value extraction"));
	return static_cast<int>((scpp::cast<scpp::int_t<>>(local_z)).native_value());
	return 0;
}
