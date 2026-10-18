#include "types/n41.hpp"
#include "scpp/shared_p.hpp"
#include "types/n43.hpp"
#include "scpp/memory.hpp"
#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	::scpp::shared_p<record_A> local_a;
	auto local_c = ([&]() -> ::scpp::shared_p<record_C> {
	::scpp::shared_p<record_A> argument_0 = local_a;
	return ::scpp::shared<record_C>(argument_0);
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
