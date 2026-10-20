#include "scpp/null_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::bool_t> local_x = scpp::nullable<scpp::bool_t>{scpp::null};
	return static_cast<int>((([&]() -> scpp::bool_t { scpp::nullable<scpp::bool_t> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::bool_t((coalesce_0).require_value("nullable required-value extraction")); } return scpp::bool_t(static_cast<bool>(static_cast<scpp::bool_t>(false)) || static_cast<bool>(static_cast<scpp::bool_t>(true))); }())).native_value());
	return 0;
}
