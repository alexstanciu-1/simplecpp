#include "scpp/int_t.hpp"
#include "callables/n415c66.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	return static_cast<int>((([&]() -> scpp::int_t<> {
	scpp::int_t<> argument_0 = static_cast<scpp::int_t<>>(0LL);
	return function_AUqf(argument_0);
}())).native_value());
	return 0;
}
