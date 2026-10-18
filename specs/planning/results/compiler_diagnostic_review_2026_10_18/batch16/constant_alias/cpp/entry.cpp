#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	return static_cast<int>((static_cast<scpp::int_t<>>(1LL)).native_value());
	return 0;
}
