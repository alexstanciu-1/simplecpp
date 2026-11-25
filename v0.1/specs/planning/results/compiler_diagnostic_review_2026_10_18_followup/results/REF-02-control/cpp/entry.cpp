#include "scpp/int_t.hpp"
#include "callables/n66.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = static_cast<scpp::int_t<>>(1LL);
	scpp::int_t<>& local_r = ([&]() -> scpp::int_t<>& {
	scpp::int_t<>& argument_0 = local_x;
	return function_f(argument_0);
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
