#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
void function_fail(scpp::int_t<>& local_target);
void function_fail(scpp::int_t<>& local_target)
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	local_target = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
}


int main()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
