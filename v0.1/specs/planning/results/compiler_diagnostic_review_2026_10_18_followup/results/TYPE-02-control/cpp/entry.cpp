#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::hash_t<scpp::int_t<>, scpp::string_t> local_s;
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
