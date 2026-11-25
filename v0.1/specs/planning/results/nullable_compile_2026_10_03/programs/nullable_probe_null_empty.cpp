#include "lang/php/operators/empty/empty.hpp"
#include "scpp/null_t.hpp"

int main()
{
	return static_cast<int>((scpp::php::empty(scpp::null)).native_value());
	return 0;
}
