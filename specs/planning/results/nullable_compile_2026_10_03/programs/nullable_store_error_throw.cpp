#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
void function_fail(scpp::int_t<>& local_target);
void function_fail(scpp::int_t<>& local_target)
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	local_target = scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
}


int unused_entry()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}

int main() { scpp::int_t<> target(7); try { (void)function_fail(target); } catch (const scpp::runtime_error& error) { return error.code() == "invalid_nullable_unwrap_empty" && target.native_value() == 7 ? 0 : 91; } return 92; }