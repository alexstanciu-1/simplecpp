#include "scpp/float_t.hpp"
#include "lang/php/support/php_string.hpp"
#include "scpp/string_t.hpp"
#include "scpp/generated/operators.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = static_cast<scpp::float_t>(1.5);
	auto local_z = ([&]() -> scpp::string_t { auto operand_0 = scpp::php::to_string(local_x); auto operand_1 = scpp::php::to_string(local_x); return (operand_0 + operand_1); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
