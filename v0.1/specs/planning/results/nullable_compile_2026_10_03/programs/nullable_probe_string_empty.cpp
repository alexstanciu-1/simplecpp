#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "scpp/bool_t.hpp"

int main()
{
	scpp::nullable<scpp::string_t> local_x = scpp::nullable<scpp::string_t>{scpp::string_t("")};
	return static_cast<int>((scpp::bool_t(static_cast<bool>(scpp::php::empty(local_x)) && static_cast<bool>(scpp::php::isset(local_x)))).native_value());
	return 0;
}
