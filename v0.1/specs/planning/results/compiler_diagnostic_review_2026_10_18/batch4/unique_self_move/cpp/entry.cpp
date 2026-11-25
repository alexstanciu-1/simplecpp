#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "utility"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::unique_p<scpp::int_t<>> local_a;
	local_a = scpp::unique_p<scpp::int_t<>>(std::move(local_a));
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
