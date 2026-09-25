#include "scpp/int_t.hpp"

int main()
{
	auto local_0 = static_cast<scpp::int_t<>>(10LL);
	auto local_4 = local_0;
	local_0 = static_cast<scpp::int_t<>>(12LL);
	return static_cast<int>((local_4).native_value());
	return 0;
}
