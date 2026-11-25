#include "scpp/string_t.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/operators/identity/strict_identity.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = scpp::string_t("1");
	auto local_z = ([&]() -> scpp::bool_t { auto operand_0 = local_x; auto operand_1 = local_x; return scpp::php::identical(operand_0, operand_1); }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
