#include "scpp/int_t.hpp"
#include "callables/n66.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	([&]() -> void {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(1LL);
	return function_f(argument_0);
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
