#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::int_t<> local_x = static_cast<scpp::int_t<>>(3LL);
	auto local_y = static_cast<scpp::int_t<>>(local_x--);
	return static_cast<int>((local_y).native_value());
	return 0;
}
