#include "scpp/int_t.hpp"
#include "callables/n415c66.hpp"
#include "callables/n425c66.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	return static_cast<int>((([&]() -> scpp::int_t<> { auto operand_0 = ([&]() -> scpp::int_t<> {
	return function_AUqf();
}()); auto operand_1 = ([&]() -> scpp::int_t<> {
	return function_BUqf();
}()); return (operand_0 + operand_1); }())).native_value());
	return 0;
}
