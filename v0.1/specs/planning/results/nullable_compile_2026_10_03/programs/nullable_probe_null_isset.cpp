#include "lang/php/operators/isset/isset.hpp"
#include "scpp/null_t.hpp"

int main()
{
	return static_cast<int>((scpp::php::isset(scpp::null)).native_value());
	return 0;
}
