#include "scpp/bool_t.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = static_cast<scpp::bool_t>(true);
	auto local_z = (!local_x);
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
