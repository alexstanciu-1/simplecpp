#include "scpp/int_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
{
	auto local_i = static_cast<scpp::int_t<>>(0LL);
	auto local_j = static_cast<scpp::int_t<>>(1LL);
for (; (([&]() -> scpp::bool_t { auto operand_0 = local_i; auto operand_1 = static_cast<scpp::int_t<>>(1LL); return (operand_0 < operand_1); }())).native_value(); (void)(static_cast<scpp::int_t<>>(local_i++)), (void)(static_cast<scpp::int_t<>>(local_j++))) {
}
}
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
