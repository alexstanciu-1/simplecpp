#include "scpp/int_t.hpp"
#include "scpp/generated/operators.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_n = static_cast<scpp::int_t<>>(1LL);
	auto local_f = [&local_n]() mutable -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(++local_n);
};
	auto local_g = [local_f]() mutable -> scpp::int_t<> {
	return ([&]() -> scpp::int_t<> {
auto callee_0 = local_f;
	return callee_0();
}());
};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_1 = local_g;
	return callee_1();
}())).native_value());
	return 0;
}
