#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = static_cast<scpp::int_t<>>(1LL);
	return static_cast<int>((local_x).native_value());
	return 0;
}
