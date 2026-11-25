#include "scpp/null_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::float_t> local_x = scpp::nullable<scpp::float_t>{scpp::null};
	return static_cast<int>((([&]() -> scpp::float_t { scpp::nullable<scpp::float_t> coalesce_0 = local_x; if (coalesce_0.has_value().native_value()) { return scpp::float_t((coalesce_0).require_value("nullable required-value extraction")); } return static_cast<scpp::float_t>(3.5); }())).native_value());
	return 0;
}
