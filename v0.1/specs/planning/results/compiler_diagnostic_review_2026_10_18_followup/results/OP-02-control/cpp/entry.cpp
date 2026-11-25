#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::int_t<> local_x = static_cast<scpp::int_t<>>(3LL);
	scpp::int_t<> local_y = static_cast<scpp::int_t<>>(3LL);
	return static_cast<int>((local_y).native_value());
	return 0;
}
