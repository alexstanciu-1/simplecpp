#include "scpp/null_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include "lang/php/operators/empty/empty.hpp"
#include "scpp/generated/operators.hpp"

int main()
{
	scpp::nullable<scpp::int_t<>> local_x = scpp::nullable<scpp::int_t<>>{scpp::null};
	auto local_a = scpp::php::isset(local_x);
	auto local_b = scpp::php::empty(local_x);
	return static_cast<int>(((!(local_x).has_value())).native_value());
	return 0;
}
