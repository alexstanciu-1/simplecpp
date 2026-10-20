#include "scpp/null_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/cast.hpp"
#include "scpp/int_t.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::null};
	auto local_y = scpp::cast<scpp::string_t>(local_x);
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
