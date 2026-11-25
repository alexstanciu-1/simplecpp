#include "scpp/bool_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::nullable<scpp::bool_t> local_x = scpp::nullable<scpp::bool_t>{static_cast<scpp::bool_t>(false)};
if (((!(local_x).has_value())).native_value()) {
	return static_cast<int>((static_cast<scpp::int_t<>>(9LL)).native_value());
}
	scpp::bool_t local_y = scpp::bool_t((local_x).require_value("nullable required-value extraction"));
	return static_cast<int>((local_y).native_value());
	return 0;
}
