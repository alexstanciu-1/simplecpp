#include "scpp/float_t.hpp"
#include "scpp/nullable.hpp"

int main()
{
	scpp::nullable<scpp::float_t> local_x = scpp::nullable<scpp::float_t>{static_cast<scpp::float_t>(3.5)};
	scpp::float_t local_y = scpp::float_t((local_x).require_value("nullable required-value extraction"));
	return static_cast<int>((local_y).native_value());
	return 0;
}
