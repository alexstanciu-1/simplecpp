#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	scpp::int_t<std::uint64_t> local_x = static_cast<scpp::int_t<std::uint64_t>>((static_cast<scpp::int_t<>>(1LL)).native_value());
	scpp::int_t<std::int8_t> local_y = static_cast<scpp::int_t<std::int8_t>>((local_x).native_value());
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
