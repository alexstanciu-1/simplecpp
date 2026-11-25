#include "types/n4c69625c45.hpp"
#include "callables/n4c69625c676574.hpp"
#include "scpp/int_t.hpp"
#include "scpp/bool_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_e = ([&]() -> enum_LibUqE {
	return function_LibUqget();
}());
	return static_cast<int>((([&]() -> scpp::int_t<> { if ((([&]() -> scpp::bool_t { auto operand_0 = local_e; auto operand_1 = enum_LibUqE::case_A; return scpp::bool_t(operand_0 == operand_1); }())).native_value()) { return static_cast<scpp::int_t<>>(1LL); } return static_cast<scpp::int_t<>>(0LL); }())).native_value());
	return 0;
}
