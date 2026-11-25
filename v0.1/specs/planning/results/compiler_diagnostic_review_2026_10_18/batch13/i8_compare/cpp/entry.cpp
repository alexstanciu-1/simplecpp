#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::int_t<std::int8_t> local_x = static_cast<scpp::int_t<std::int8_t>>((static_cast<scpp::int_t<>>(1LL)).native_value());
	auto local_z = ([&]() -> scpp::int_t<> { auto operand_0 = local_x; auto operand_1 = local_x; return scpp::int_t<>(static_cast<bool>(operand_0 < operand_1) ? -1 : (static_cast<bool>(operand_0 > operand_1) ? 1 : 0)); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
