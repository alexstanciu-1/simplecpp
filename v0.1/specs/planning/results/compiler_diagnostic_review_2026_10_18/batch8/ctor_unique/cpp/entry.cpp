#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "types/n43.hpp"
#include "scpp/memory.hpp"
#include "scpp/shared_p.hpp"
#include "utility"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::unique_p<scpp::int_t<>> local_p;
	auto local_c = ([&]() -> ::scpp::shared_p<record_C> {
	scpp::unique_p<scpp::int_t<>> argument_0 = scpp::unique_p<scpp::int_t<>>(std::move(local_p));
	return ::scpp::shared<record_C>(std::move(argument_0));
}());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
