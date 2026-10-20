#include "lang/php/operators/empty/empty.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "scpp/bool_t.hpp"

int main()
{
	return static_cast<int>((scpp::bool_t(static_cast<bool>(scpp::php::empty(static_cast<scpp::int_t<>>(0LL))) && static_cast<bool>(scpp::php::isset(static_cast<scpp::int_t<>>(0LL))))).native_value());
	return 0;
}
