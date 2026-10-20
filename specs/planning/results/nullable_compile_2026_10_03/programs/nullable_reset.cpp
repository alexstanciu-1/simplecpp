#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)};
	local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return static_cast<int>(((!(local_x).has_value())).native_value());
	return 0;
}
