#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
scpp::int_t<> function_fail();
scpp::int_t<> function_fail()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return scpp::int_t<>((local_x).require_value("nullable required-value extraction"));
}


int unused_entry()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}

int main() { try { (void)function_fail(); } catch (const scpp::runtime_error& error) { return error.code() == "invalid_nullable_unwrap_empty" ? 0 : 91; } return 92; }
