#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::mixed_t local_x = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)};
	auto local_z = (-local_x);
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
