#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(3LL)};
	auto local_y = ([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(7LL); }());
	local_y = static_cast<scpp::int_t<>>(5LL);
	scpp::int_t<> local_z = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
	return static_cast<int>((local_z).native_value());
	return 0;
}
