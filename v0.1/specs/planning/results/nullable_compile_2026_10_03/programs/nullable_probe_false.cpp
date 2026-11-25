#include "scpp/bool_t.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "lang/php/operators/isset/isset.hpp"

int main()
{
	scpp::nullable<scpp::bool_t> local_x = scpp::nullable<scpp::bool_t>{static_cast<scpp::bool_t>(false)};
	return static_cast<int>((scpp::bool_t(static_cast<bool>(scpp::php::empty(local_x)) && static_cast<bool>(scpp::php::isset(local_x)))).native_value());
	return 0;
}
