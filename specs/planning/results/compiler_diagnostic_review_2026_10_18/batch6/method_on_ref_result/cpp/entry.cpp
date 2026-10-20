#include "types/n53.hpp"
#include "callables/n6964.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_s = record_S{};
	return static_cast<int>((([&]() -> scpp::int_t<> {
auto& receiver_1 = ([&]() -> record_S& {
	record_S& argument_0 = local_s;
	return function_id(argument_0);
}());
	return receiver_1.method_f();
}())).native_value());
	return 0;
}
