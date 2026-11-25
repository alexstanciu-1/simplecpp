#include "types/n4c69625c58.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/int_t.hpp"
#include "callables/n4c69625c58.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_x = ::scpp::shared<record_LibUqX>();
	return static_cast<int>((([&]() -> scpp::int_t<> {
	return function_LibUqX();
}())).native_value());
	return 0;
}
