#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_n = static_cast<scpp::int_t<>>(256LL);
	scpp::int_t<std::uint8_t> local_b = static_cast<scpp::int_t<std::uint8_t>>((local_n).native_value());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
