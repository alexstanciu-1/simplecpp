#include "scpp/bool_t.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
scpp::bool_t function_fail();
scpp::bool_t function_fail()
{
	return scpp::php::empty((static_cast<scpp::int_t<>>(1LL) / static_cast<scpp::int_t<>>(0LL)));
}


int unused_entry()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}

int main() { try { (void)function_fail(); } catch (const scpp::runtime_error& error) { return error.code() == "division_by_zero" ? 0 : 91; } return 92; }
