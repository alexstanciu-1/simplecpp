#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"

int main()
{
	auto local_x = static_cast<scpp::bool_t>(true);
	([&]() -> scpp::int_t<> { if ((local_x).native_value()) { return static_cast<scpp::int_t<>>(9223372036854775807LL); } return static_cast<scpp::int_t<>>(2LL); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
