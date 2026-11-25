#include "types/n53.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/memory.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	::scpp::shared_p<record_S> local_s;
	auto local_t = ([&]() -> ::scpp::shared_p<record_S> {
	::scpp::shared_p<record_S> argument_0 = local_s;
	return ::scpp::shared<record_S>(argument_0);
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
