#include "types/n4c69625c53.hpp"
#include "callables/n4c69625c6d616b65.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_s = ([&]() -> record_LibUqS {
	return function_LibUqmake();
}());
	return static_cast<int>(((local_s).field_n).native_value());
	return 0;
}
