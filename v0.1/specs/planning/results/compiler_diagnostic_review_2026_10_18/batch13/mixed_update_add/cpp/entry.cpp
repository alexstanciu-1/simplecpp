#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::mixed_t local_x = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)};
	([&]() -> scpp::mixed_t { auto& target_0 = local_x; auto previous_1 = target_0; scpp::detail::assign_numeric_result(previous_1, ([&]() -> scpp::mixed_t { auto operand_2 = previous_1; auto operand_3 = scpp::mixed_t{static_cast<scpp::int_t<>>(1LL)}; return (operand_2 + operand_3); }())); target_0 = previous_1; return target_0; }());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
