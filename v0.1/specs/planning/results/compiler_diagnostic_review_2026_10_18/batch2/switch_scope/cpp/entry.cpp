#include "scpp/int_t.hpp"
#include "lang/php/cli_context.hpp"

int main(int argc, char** argv)
{
	scpp::php::set_cli_args(argc, argv);
	auto local_n = static_cast<scpp::int_t<>>(1LL);
switch ((local_n).native_value()) {
case 1LL:
{
	auto local_x = static_cast<scpp::int_t<>>(1LL);
}
case 2LL:
{
	auto local_x = static_cast<scpp::int_t<>>(2LL);
break;
}
}
	return static_cast<int>((static_cast<scpp::int_t<>>(0LL)).native_value());
	return 0;
}
