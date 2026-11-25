#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_y = ([&]() -> scpp::nullable<scpp::int_t<>> { if ((static_cast<scpp::bool_t>(true)).native_value()) { return local_x; } return scpp::nullable<scpp::int_t<>>{static_cast<scpp::int_t<>>(7LL)}; }());
	return static_cast<int>(((!(local_y).has_value())).native_value());
	return 0;
}
