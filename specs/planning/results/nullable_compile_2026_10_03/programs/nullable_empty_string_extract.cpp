#include "scpp/string_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/int_t.hpp"
scpp::string_t function_fail();
scpp::string_t function_fail()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::null};
	return scpp::string_t((local_x).require_value("nullable required-value extraction"));
}


int main()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
