#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(4LL)};
	scpp::nullable<scpp::int_t<>> local_y = local_x;
	local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	scpp::int_t<> local_z = scpp::int_t<>((local_y).require_value("nullable required-value extraction"));
	return static_cast<int>((local_z).native_value());
	return 0;
}
