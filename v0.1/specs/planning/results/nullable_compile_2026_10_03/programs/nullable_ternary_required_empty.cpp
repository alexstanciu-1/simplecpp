#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"
scpp::int_t<> function_fail();
scpp::int_t<> function_fail()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	return scpp::int_t<>((([&]() -> scpp::nullable<scpp::int_t<>> { if ((static_cast<scpp::bool_t>(true)).native_value()) { return local_x; } return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)}; }())).require_value("nullable required-value extraction"));
}


int main()
{
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
