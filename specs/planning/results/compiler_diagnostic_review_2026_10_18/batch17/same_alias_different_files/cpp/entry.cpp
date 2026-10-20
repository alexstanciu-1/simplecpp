#include "scpp/int_t.hpp"
#include "callables/n4170705c6c656674.hpp"
#include "callables/n4170705c7269676874.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	return static_cast<int>((([&]() -> scpp::int_t<> { auto operand_0 = ([&]() -> scpp::int_t<> {
	return function_AppUqleft();
}()); auto operand_1 = ([&]() -> scpp::int_t<> {
	return function_AppUqright();
}()); return (operand_0 + operand_1); }())).native_value());
	return 0;
}
