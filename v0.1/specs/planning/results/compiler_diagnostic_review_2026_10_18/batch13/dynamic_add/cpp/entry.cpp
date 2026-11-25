#include "scpp/int_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::dynamic_t<> local_x = scpp::dynamic_t<>{static_cast<scpp::int_t<>>(1LL)};
	auto local_z = ([&]() -> scpp::dynamic_t<> { auto operand_0 = local_x; auto operand_1 = local_x; return (operand_0 + operand_1); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
