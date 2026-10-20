#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return static_cast<int>((([&]() -> scpp::int_t<> { scpp::nullable<scpp::int_t<>> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::int_t<>((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::int_t<>>(7LL); }())).native_value());
	return 0;
}
