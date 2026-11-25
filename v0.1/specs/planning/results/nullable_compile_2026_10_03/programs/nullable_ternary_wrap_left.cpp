#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_y = ([&]() -> scpp::nullable<scpp::int_t<>> { if ((static_cast<scpp::bool_t>(true)).native_value()) { return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)}; } return local_x; }());
	return static_cast<int>((([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_y; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(9LL); }())).native_value());
	return 0;
}
