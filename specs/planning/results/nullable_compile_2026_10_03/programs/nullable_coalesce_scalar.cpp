#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	return static_cast<int>((([&]() -> scpp::int_t<> { return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
