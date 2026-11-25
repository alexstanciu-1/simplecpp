#include "lang/php/operators/empty/empty.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/operators/isset/isset.hpp"

int main()
{
	return static_cast<int>((scpp::bool_t(static_cast<bool>(scpp::php::empty(static_cast<scpp::bool_t>(false))) && static_cast<bool>(scpp::php::isset(static_cast<scpp::bool_t>(false))))).native_value());
	return 0;
}
