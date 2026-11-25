#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(8LL)};
	scpp::int_t<> local_y = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
	return static_cast<int>((local_y).native_value());
	return 0;
}
