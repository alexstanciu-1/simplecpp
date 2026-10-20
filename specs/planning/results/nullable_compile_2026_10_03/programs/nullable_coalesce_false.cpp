#include "scpp/bool_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::bool_t> local_x = scpp::nullable<scpp::bool_t>{static_cast<scpp::bool_t>(false)};
	return static_cast<int>((([&]() -> scpp::bool_t { scpp::nullable<scpp::bool_t> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::bool_t((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::bool_t>(true); }())).native_value());
	return 0;
}
