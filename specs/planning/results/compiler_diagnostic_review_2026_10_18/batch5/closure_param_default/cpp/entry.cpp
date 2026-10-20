#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "utility"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_n = static_cast<scpp::int_t<>>(1LL);
	auto local_f = [local_n](scpp::int_t<> local_x) mutable -> scpp::int_t<> {
	return ([&]() -> scpp::int_t<> { auto operand_0 = local_x; auto operand_1 = local_n; return (operand_0 + operand_1); }());
};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_2 = local_f;
scpp::int_t<> default_argument_3 = static_cast<scpp::int_t<>>(2LL);
	return callee_2(std::move(default_argument_3));
}())).native_value());
	return 0;
}
