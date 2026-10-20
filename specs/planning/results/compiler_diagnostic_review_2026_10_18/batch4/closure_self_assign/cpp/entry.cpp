#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_f = []() -> scpp::int_t<> {
	return static_cast<scpp::int_t<>>(1LL);
};
	local_f = local_f;
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto callee_0 = local_f;
	return callee_0();
}())).native_value());
	return 0;
}
